package scraper

import (
	"context"
	"encoding/json"
	"fmt"
	"linkedin-user-scraper/internal/config"
	"linkedin-user-scraper/internal/models"
	"linkedin-user-scraper/internal/services"
	"os"
	"strings"
	"time"

	"github.com/chromedp/cdproto/runtime"
	"github.com/chromedp/chromedp"
	"github.com/sirupsen/logrus"
)

type LinkedInUserScraper struct {
	config      *config.Config
	dataService *services.DataService
}

// NewLinkedInUserScraper creates a new LinkedIn user scraper
func NewLinkedInUserScraper(cfg *config.Config, dataService *services.DataService) *LinkedInUserScraper {
	return &LinkedInUserScraper{
		config:      cfg,
		dataService: dataService,
	}
}

// ScrapeUser scrapes a LinkedIn user profile
func (s *LinkedInUserScraper) ScrapeUser(username string) error {
	logrus.Infof("🚀 Starting LinkedIn user scraper for: %s", username)

	// Setup Chrome options
	opts := append(chromedp.DefaultExecAllocatorOptions[:],
		chromedp.ExecPath(s.config.Scraper.ChromeExecutablePath),
		chromedp.Flag("headless", s.config.Scraper.HeadlessBrowser),
		chromedp.Flag("disable-gpu", true),
		chromedp.Flag("no-sandbox", true),
		chromedp.Flag("disable-dev-shm-usage", true),
		chromedp.Flag("disable-web-security", true),
		chromedp.Flag("disable-features", "VizDisplayCompositor"),
		chromedp.Flag("disable-extensions", true),
		chromedp.Flag("disable-plugins", true),
		chromedp.Flag("disable-images", true),
		chromedp.UserDataDir(s.config.Scraper.UserDataDir),
	)

	allocCtx, cancel := chromedp.NewExecAllocator(context.Background(), opts...)
	defer cancel()

	ctx, cancel := chromedp.NewContext(allocCtx, chromedp.WithLogf(func(s string, args ...interface{}) {
		if !strings.Contains(s, "cookiePart") && !strings.Contains(s, "could not unmarshal event") {
			logrus.Debugf("ChromeDP: "+s, args...)
		}
	}))
	defer cancel()

	// Enable console logging for debugging
	debugScraper := strings.ToLower(os.Getenv("DEBUG_SCRAPER")) == "true"
	if debugScraper {
		logrus.Info("🐛 Debug mode enabled - will show JavaScript console output")
	}
	
	chromedp.ListenTarget(ctx, func(ev interface{}) {
		if !debugScraper {
			return
		}

		switch ev := ev.(type) {
		case *runtime.EventConsoleAPICalled:
			args := make([]string, len(ev.Args))
			for i, arg := range ev.Args {
				if arg.Value != nil {
					args[i] = string(arg.Value)
				} else {
					args[i] = "null"
				}
			}
			message := strings.Join(args, " ")
			logrus.Infof("JS: %s", message)
		}
	})

	// Login to LinkedIn
	if err := s.login(ctx); err != nil {
		return fmt.Errorf("login failed: %w", err)
	}

	// Enable console domain if debugging
	if debugScraper {
		err := chromedp.Run(ctx, runtime.Enable())
		if err != nil {
			logrus.Warnf("⚠️ Could not enable runtime console: %v", err)
		} else {
			logrus.Debug("✅ Runtime console domain enabled")
		}
	}

	// Check if user already exists
	linkedinURL := fmt.Sprintf("https://www.linkedin.com/in/%s/", username)
	exists, err := s.dataService.CheckUserExists(linkedinURL)
	if err != nil {
		logrus.Warnf("⚠️  Could not check if user exists: %v", err)
	} else if exists {
		logrus.Infof("⏭️  User already exists, skipping: %s", username)
		return nil
	}

	// Navigate to user profile
	logrus.Infof("🔍 Navigating to profile: %s", linkedinURL)
	err = chromedp.Run(ctx,
		chromedp.Navigate(linkedinURL),
		chromedp.WaitVisible(`body`, chromedp.ByQuery),
		chromedp.Sleep(3*time.Second),
	)

	if err != nil {
		return fmt.Errorf("failed to navigate to profile: %w", err)
	}

	// Check if profile exists (not 404)
	var pageTitle string
	err = chromedp.Run(ctx,
		chromedp.Title(&pageTitle),
	)
	if err != nil {
		return fmt.Errorf("failed to get page title: %w", err)
	}

	if strings.Contains(strings.ToLower(pageTitle), "page not found") || 
	   strings.Contains(strings.ToLower(pageTitle), "404") {
		return fmt.Errorf("user profile not found: %s", username)
	}

	// Scrape user data
	logrus.Info("📊 Scraping user profile data...")
	user, err := s.extractUserData(ctx, username)
	if err != nil {
		return fmt.Errorf("failed to extract user data: %w", err)
	}

	// Save user to database
	logrus.Info("💾 Saving user to database...")
	if err := s.dataService.SaveUser(user); err != nil {
		return fmt.Errorf("failed to save user: %w", err)
	}

	logrus.Infof("✅ Successfully scraped user: %s", username)
	return nil
}

// ProcessUsersFromQueue processes users from the Redis queue
func (s *LinkedInUserScraper) ProcessUsersFromQueue(limit int) error {
	logrus.Infof("🔄 Processing users from queue (limit: %d)", limit)

	processed := 0
	for processed < limit {
		// Get user from queue
		username, err := s.dataService.GetUserFromQueue()
		if err != nil {
			return fmt.Errorf("failed to get user from queue: %w", err)
		}

		if username == "" {
			logrus.Info("📭 Queue is empty")
			break
		}

		logrus.Infof("🔄 Processing user %d/%d: %s", processed+1, limit, username)

		// Extract username from URL if full URL was provided
		if strings.Contains(username, "linkedin.com/in/") {
			parts := strings.Split(username, "/in/")
			if len(parts) > 1 {
				username = strings.TrimSuffix(parts[1], "/")
			}
		}

		// Scrape the user
		if err := s.ScrapeUser(username); err != nil {
			logrus.Errorf("❌ Failed to scrape user %s: %v", username, err)
			// Continue with next user instead of failing completely
			continue
		}

		processed++

		// Add delay between requests
		if processed < limit {
			time.Sleep(time.Duration(s.config.Scraper.DelayBetweenRequests) * time.Second)
		}
	}

	logrus.Infof("✅ Processed %d users", processed)
	return nil
}

// extractUserData extracts user data from the LinkedIn profile page
func (s *LinkedInUserScraper) extractUserData(ctx context.Context, username string) (*models.User, error) {
	// Wait for page content to load and try to prepare for extraction
	logrus.Debug("🔍 Preparing page for data extraction...")
	err := chromedp.Run(ctx,
		// Wait for main profile content
		chromedp.WaitVisible(`main, #main-content, .application-outlet`, chromedp.ByQuery),
		chromedp.Sleep(2*time.Second),
		
		// Scroll down to trigger lazy loading of experience/education sections
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight/4);`, nil),
		chromedp.Sleep(1*time.Second),
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight/2);`, nil),
		chromedp.Sleep(1*time.Second),
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight*3/4);`, nil),
		chromedp.Sleep(1*time.Second),
		chromedp.Evaluate(`window.scrollTo(0, document.body.scrollHeight);`, nil),
		chromedp.Sleep(2*time.Second),
		chromedp.Evaluate(`window.scrollTo(0, 0);`, nil),
		chromedp.Sleep(1*time.Second),
		
		// Wait for profile card or top card to be visible
		chromedp.ActionFunc(func(ctx context.Context) error {
			var selectors = []string{
				`[data-view-name="profile-card"]`,
				`.pv-top-card`,
				`h1[class*="heading"]`,
				`.scaffold-layout__main`,
			}
			
			for _, selector := range selectors {
				err := chromedp.WaitVisible(selector, chromedp.ByQuery).Do(ctx)
				if err == nil {
					logrus.Debugf("✅ Found profile content with selector: %s", selector)
					return nil
				}
			}
			logrus.Warn("⚠️ Could not find standard profile selectors, proceeding anyway...")
			return nil
		}),
		
		// Additional wait for JavaScript modules to load
		chromedp.Sleep(3*time.Second),
	)
	
	if err != nil {
		logrus.Warnf("⚠️ Could not fully prepare page: %v", err)
		// Continue with extraction anyway
	}

	var profileDataStr string

	// Execute JavaScript to extract profile data with retries
	logrus.Debug("🔍 Executing JavaScript extraction script...")
	for attempt := 1; attempt <= 3; attempt++ {
		err = chromedp.Run(ctx,
			chromedp.Evaluate(s.buildProfileExtractionScript(), &profileDataStr),
		)
		
		if err != nil {
			logrus.Warnf("❌ JavaScript extraction attempt %d failed: %v", attempt, err)
			if attempt < 3 {
				time.Sleep(2 * time.Second)
				continue
			}
			return nil, fmt.Errorf("failed to extract profile data after %d attempts: %w", attempt, err)
		}
		
		// Check if we got meaningful data
		if profileDataStr != "" && profileDataStr != "null" && profileDataStr != "{}" {
			logrus.Debug("✅ JavaScript extraction successful")
			break
		}
		
		if attempt < 3 {
			logrus.Warnf("⚠️ JavaScript extraction attempt %d returned empty data, retrying...", attempt)
			time.Sleep(2 * time.Second)
		}
	}

	if err != nil {
		return nil, fmt.Errorf("failed to extract profile data: %w", err)
	}

	logrus.Debugf("Raw profile data extracted: %s", profileDataStr)

	if profileDataStr == "" {
		return nil, fmt.Errorf("no profile data found")
	}

	// Parse the JSON response
	var profileData map[string]interface{}
	if err := json.Unmarshal([]byte(profileDataStr), &profileData); err != nil {
		return nil, fmt.Errorf("failed to parse profile data: %w", err)
	}

	// Convert to User model
	user := &models.User{
		LinkedInURL:      fmt.Sprintf("https://www.linkedin.com/in/%s/", username),
		LinkedInSyncedAt: timePtr(time.Now()),
	}

	// Extract basic profile information
	if profile, ok := profileData["profile"].(map[string]interface{}); ok {
		// LinkedIn ID - try multiple possible fields
		if entityUrn, ok := profile["entityUrn"].(string); ok {
			user.LinkedInID = entityUrn
		} else if objectUrn, ok := profile["objectUrn"].(string); ok {
			user.LinkedInID = objectUrn
		} else if publicUrn, ok := profile["publicContactInfo"].(map[string]interface{}); ok {
			if urn, ok := publicUrn["entityUrn"].(string); ok {
				user.LinkedInID = urn
			}
		}
		
		if headline, ok := profile["headline"].(string); ok {
			user.Headline = headline
		}
		if summary, ok := profile["summary"].(string); ok {
			user.Summary = summary
		}
		
		// Location handling - try multiple fields
		if location, ok := profile["geoLocationName"].(string); ok {
			user.LocationCity = location
		} else if locationName, ok := profile["locationName"].(string); ok {
			user.LocationCity = locationName
		}
		
		if country, ok := profile["geoCountryName"].(string); ok {
			user.LocationCountry = country
		}
		
		if industry, ok := profile["industryName"].(string); ok {
			user.IndustryName = industry
		}
		
		// Try to extract first and last name if available
		if firstName, ok := profile["firstName"].(string); ok {
			if lastName, ok := profile["lastName"].(string); ok {
				// You might want to add name fields to your user model
				logrus.Debugf("Found name: %s %s", firstName, lastName)
			}
		}
	}

	// Extract miniProfile information
	if miniProfile, ok := profileData["miniProfile"].(map[string]interface{}); ok {
		if publicId, ok := miniProfile["publicIdentifier"].(string); ok {
			user.LinkedInURL = fmt.Sprintf("https://www.linkedin.com/in/%s/", publicId)
		}
		
		// LinkedIn ID from miniProfile if not found in profile
		if user.LinkedInID == "" {
			if objectUrn, ok := miniProfile["objectUrn"].(string); ok {
				user.LinkedInID = objectUrn
			} else if entityUrn, ok := miniProfile["entityUrn"].(string); ok {
				user.LinkedInID = entityUrn
			}
		}
		
		// Extract profile picture
		if picture, ok := miniProfile["picture"].(map[string]interface{}); ok {
			if rootUrl, ok := picture["rootUrl"].(string); ok {
				// Handle both simple rootUrl and artifacts structure
				if artifacts, ok := picture["artifacts"].([]interface{}); ok && len(artifacts) > 0 {
					if artifact, ok := artifacts[0].(map[string]interface{}); ok {
						if fileIdent, ok := artifact["fileIdentifyingUrlPathSegment"].(string); ok {
							user.Avatar = rootUrl + fileIdent
						}
					}
				} else {
					// Direct rootUrl
					user.Avatar = rootUrl
				}
			}
		}
		
		// Extract background image
		if bgImage, ok := miniProfile["backgroundImage"].(map[string]interface{}); ok {
			if rootUrl, ok := bgImage["rootUrl"].(string); ok {
				if artifacts, ok := bgImage["artifacts"].([]interface{}); ok && len(artifacts) > 0 {
					if artifact, ok := artifacts[0].(map[string]interface{}); ok {
						if fileIdent, ok := artifact["fileIdentifyingUrlPathSegment"].(string); ok {
							user.BackgroundImage = rootUrl + fileIdent
						}
					}
				} else {
					user.BackgroundImage = rootUrl
				}
			}
		}
	}

	// Extract positions with comprehensive company data
	if positions, ok := profileData["positionView"].(map[string]interface{}); ok {
		if elements, ok := positions["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if pos, ok := elem.(map[string]interface{}); ok {
					position := models.Position{
						UserID: user.ID, // Will be set after user is saved
					}
					
					if title, ok := pos["title"].(string); ok {
						position.Title = title
					}
					if companyName, ok := pos["companyName"].(string); ok {
						position.CompanyName = companyName
					}
					if companyUrn, ok := pos["companyUrn"].(string); ok {
						position.CompanyUrn = companyUrn
					}
					
					// Extract job description/summary
					if description, ok := pos["description"].(string); ok {
						position.Summary = description
					}
					
					// Extract location
					if location, ok := pos["location"].(string); ok {
						position.Location = location
					}
					
					// Extract company information
					if company, ok := pos["company"].(map[string]interface{}); ok {
						if industries, ok := company["industries"].([]interface{}); ok && len(industries) > 0 {
							if industry, ok := industries[0].(string); ok {
								position.CompanyIndustry = industry
							}
						}
						if employeeRange, ok := company["employeeCountRange"].(map[string]interface{}); ok {
							if start, ok := employeeRange["start"].(float64); ok {
								if end, ok := employeeRange["end"].(float64); ok {
									position.CompanyEmployeeRange = fmt.Sprintf("%.0f-%.0f", start, end)
								}
							}
						}
						if miniCompany, ok := company["miniCompany"].(map[string]interface{}); ok {
							if logo, ok := miniCompany["logo"].(map[string]interface{}); ok {
								if logoBytes, err := json.Marshal(logo); err == nil {
									position.LogoJSON = string(logoBytes)
								}
							}
						}
					}
					
					// Extract time period
					if timePeriod, ok := pos["timePeriod"].(map[string]interface{}); ok {
						if startDate, ok := timePeriod["startDate"].(map[string]interface{}); ok {
							if year, ok := startDate["year"].(float64); ok {
								position.StartYear = intPtr(int(year))
								// Create start date
								month := 1 // Default to January if no month specified
								if monthVal, ok := startDate["month"].(float64); ok {
									month = int(monthVal)
									position.StartMonth = intPtr(month)
								}
								startTime := time.Date(int(year), time.Month(month), 1, 0, 0, 0, 0, time.UTC)
								position.StartDate = &startTime
							}
						}
						if endDate, ok := timePeriod["endDate"].(map[string]interface{}); ok {
							if year, ok := endDate["year"].(float64); ok {
								position.EndYear = intPtr(int(year))
								// Create end date
								month := 12 // Default to December if no month specified
								if monthVal, ok := endDate["month"].(float64); ok {
									month = int(monthVal)
									position.EndMonth = intPtr(month)
								}
								// Use last day of the month for end date
								endTime := time.Date(int(year), time.Month(month+1), 1, 0, 0, 0, 0, time.UTC).AddDate(0, 0, -1)
								position.EndDate = &endTime
							}
						}
					}
					
					user.Positions = append(user.Positions, position)
				}
			}
		}
	}

	// Extract education with comprehensive school data
	if education, ok := profileData["educationView"].(map[string]interface{}); ok {
		if elements, ok := education["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if edu, ok := elem.(map[string]interface{}); ok {
					educ := models.Education{
						UserID: user.ID, // Will be set after user is saved
					}
					
					if schoolName, ok := edu["schoolName"].(string); ok {
						educ.SchoolName = schoolName
					}
					if degree, ok := edu["degree"].(string); ok {
						educ.Degree = degree
					} else if degreeName, ok := edu["degreeName"].(string); ok {
						educ.Degree = degreeName
					}
					if fieldOfStudy, ok := edu["fieldOfStudy"].(string); ok {
						educ.FieldOfStudy = fieldOfStudy
					} else if field, ok := edu["fieldsOfStudy"].([]interface{}); ok && len(field) > 0 {
						if fieldStr, ok := field[0].(string); ok {
							educ.FieldOfStudy = fieldStr
						}
					}
					if schoolUrn, ok := edu["schoolUrn"].(string); ok {
						educ.SchoolUrn = schoolUrn
					}
					
					// Extract school logo
					if school, ok := edu["school"].(map[string]interface{}); ok {
						if logo, ok := school["logo"].(map[string]interface{}); ok {
							if logoBytes, err := json.Marshal(logo); err == nil {
								educ.LogoJSON = string(logoBytes)
							}
						}
					}
					
					// Extract time period
					if timePeriod, ok := edu["timePeriod"].(map[string]interface{}); ok {
						if startDate, ok := timePeriod["startDate"].(map[string]interface{}); ok {
							if year, ok := startDate["year"].(float64); ok {
								educ.StartYear = intPtr(int(year))
							}
						}
						if endDate, ok := timePeriod["endDate"].(map[string]interface{}); ok {
							if year, ok := endDate["year"].(float64); ok {
								educ.EndYear = intPtr(int(year))
							}
						}
					}
					
					user.Educations = append(user.Educations, educ)
				}
			}
		}
	}

	// Extract skill URLs from the JavaScript data but don't process them yet
	logrus.Debug("🔍 Collecting skill URLs for later processing...")
	
	var skillUrls []map[string]interface{}
	if skillUrlData, ok := profileData["skillUrls"].(map[string]interface{}); ok {
		if positionUrls, ok := skillUrlData["positions"].([]interface{}); ok {
			for _, url := range positionUrls {
				if urlMap, ok := url.(map[string]interface{}); ok {
					skillUrls = append(skillUrls, urlMap)
				}
			}
		}
		if educationUrls, ok := skillUrlData["education"].([]interface{}); ok {
			for _, url := range educationUrls {
				if urlMap, ok := url.(map[string]interface{}); ok {
					skillUrls = append(skillUrls, urlMap)
				}
			}
		}
	}
	
	logrus.Infof("🔍 Found %d skill URLs to process after basic profile extraction", len(skillUrls))
	
	// Process all skill URLs AFTER extracting all basic profile data
	logrus.Info("🔍 Now processing all skill URLs by navigating to overlay pages...")
	skillFrequencyMap := make(map[string]int)
	
	for i, skillUrlData := range skillUrls {
		url, _ := skillUrlData["url"].(string)
		itemType, _ := skillUrlData["type"].(string)
		title, _ := skillUrlData["title"].(string)
		
		if url == "" {
			continue
		}
		
		logrus.Infof("🔍 [%d/%d] Extracting skills for %s: %s", i+1, len(skillUrls), itemType, title)
		
		// Extract skills from this URL
		skills, err := s.extractSkillsFromUrl(ctx, url, fmt.Sprintf("%s '%s'", itemType, title))
		if err != nil {
			logrus.Warnf("⚠️ Failed to extract skills from %s: %v", url, err)
			continue
		}
		
		// Add skills to the appropriate item
		if itemType == "position" {
			// Match by title since we don't have positionIndex anymore
			for j := range user.Positions {
				if strings.EqualFold(user.Positions[j].Title, title) {
					for _, skillName := range skills {
						skillModel := models.Skill{Name: skillName}
						user.Positions[j].Skills = append(user.Positions[j].Skills, skillModel)
						skillFrequencyMap[skillName]++
					}
					logrus.Infof("✅ Added %d skills to position: %s", len(skills), title)
					break
				}
			}
		} else if itemType == "education" {
			// Match by school name since we don't have educationIndex anymore
			for j := range user.Educations {
				if strings.EqualFold(user.Educations[j].SchoolName, title) {
					for _, skillName := range skills {
						skillModel := models.Skill{Name: skillName}
						user.Educations[j].Skills = append(user.Educations[j].Skills, skillModel)
						skillFrequencyMap[skillName]++
					}
					logrus.Infof("✅ Added %d skills to education: %s", len(skills), title)
					break
				}
			}
		}
		
		// Add a small delay between skill URL processing to avoid being too aggressive
		if i < len(skillUrls)-1 {
			time.Sleep(1 * time.Second)
		}
	}
	
	// Create skill frequency list
	for skillName, frequency := range skillFrequencyMap {
		skillFreq := models.SkillFrequency{
			Skill:     models.Skill{Name: skillName},
			Frequency: frequency,
		}
		user.SkillFrequencies = append(user.SkillFrequencies, skillFreq)
	}
	
	// Sort skills by frequency (most frequent first)
	for i := 0; i < len(user.SkillFrequencies)-1; i++ {
		for j := i + 1; j < len(user.SkillFrequencies); j++ {
			if user.SkillFrequencies[i].Frequency < user.SkillFrequencies[j].Frequency {
				user.SkillFrequencies[i], user.SkillFrequencies[j] = user.SkillFrequencies[j], user.SkillFrequencies[i]
			}
		}
	}
	
	// Create unique skills list
	uniqueSkills := make(map[string]bool)
	for _, freq := range user.SkillFrequencies {
		if !uniqueSkills[freq.Skill.Name] {
			user.Skills = append(user.Skills, freq.Skill)
			uniqueSkills[freq.Skill.Name] = true
		}
	}
	
	logrus.Infof("📊 Skill extraction complete: %d unique skills found, %d total skill mentions", len(user.Skills), len(skillFrequencyMap))
	if len(user.SkillFrequencies) > 0 {
		topSkillsLog := fmt.Sprintf("🏆 Top skills: %s (%d)", user.SkillFrequencies[0].Skill.Name, user.SkillFrequencies[0].Frequency)
		if len(user.SkillFrequencies) > 1 {
			topSkillsLog += fmt.Sprintf(", %s (%d)", user.SkillFrequencies[1].Skill.Name, user.SkillFrequencies[1].Frequency)
		}
		if len(user.SkillFrequencies) > 2 {
			topSkillsLog += fmt.Sprintf(", %s (%d)", user.SkillFrequencies[2].Skill.Name, user.SkillFrequencies[2].Frequency)
		}
		logrus.Info(topSkillsLog)
	}

	// Extract certifications
	if certifications, ok := profileData["certificationView"].(map[string]interface{}); ok {
		if elements, ok := certifications["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if cert, ok := elem.(map[string]interface{}); ok {
					certification := models.Certification{
						UserID: user.ID,
					}
					
					if name, ok := cert["name"].(string); ok {
						certification.Name = name
					}
					if authority, ok := cert["authority"].(string); ok {
						certification.Authority = authority
					}
					if licenseNumber, ok := cert["licenseNumber"].(string); ok {
						certification.LicenseNumber = licenseNumber
					}
					if url, ok := cert["url"].(string); ok {
						certification.URL = url
					}
					
					// Extract time period
					if timePeriod, ok := cert["timePeriod"].(map[string]interface{}); ok {
						if startDate, ok := timePeriod["startDate"].(map[string]interface{}); ok {
							if year, ok := startDate["year"].(float64); ok {
								certification.StartYear = intPtr(int(year))
							}
							if month, ok := startDate["month"].(float64); ok {
								certification.StartMonth = intPtr(int(month))
							}
						}
						if endDate, ok := timePeriod["endDate"].(map[string]interface{}); ok {
							if year, ok := endDate["year"].(float64); ok {
								certification.EndYear = intPtr(int(year))
							}
							if month, ok := endDate["month"].(float64); ok {
								certification.EndMonth = intPtr(int(month))
							}
						}
					}
					
					user.Certifications = append(user.Certifications, certification)
				}
			}
		}
	}

	// Extract projects
	if projects, ok := profileData["projectView"].(map[string]interface{}); ok {
		if elements, ok := projects["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if proj, ok := elem.(map[string]interface{}); ok {
					project := models.Project{
						UserID: user.ID,
					}
					
					if title, ok := proj["title"].(string); ok {
						project.Title = title
					}
					if description, ok := proj["description"].(string); ok {
						project.Description = description
					}
					if url, ok := proj["url"].(string); ok {
						project.URL = url
					}
					
					// Extract time period
					if timePeriod, ok := proj["timePeriod"].(map[string]interface{}); ok {
						if startDate, ok := timePeriod["startDate"].(map[string]interface{}); ok {
							if year, ok := startDate["year"].(float64); ok {
								project.StartYear = intPtr(int(year))
							}
							if month, ok := startDate["month"].(float64); ok {
								project.StartMonth = intPtr(int(month))
							}
						}
						if endDate, ok := timePeriod["endDate"].(map[string]interface{}); ok {
							if year, ok := endDate["year"].(float64); ok {
								project.EndYear = intPtr(int(year))
							}
							if month, ok := endDate["month"].(float64); ok {
								project.EndMonth = intPtr(int(month))
							}
						}
					}
					
					user.Projects = append(user.Projects, project)
				}
			}
		}
	}

	// Extract publications
	if publications, ok := profileData["publicationView"].(map[string]interface{}); ok {
		if elements, ok := publications["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if pub, ok := elem.(map[string]interface{}); ok {
					publication := models.Publication{
						UserID: user.ID,
					}
					
					if title, ok := pub["title"].(string); ok {
						publication.Title = title
					}
					if publisher, ok := pub["publisher"].(string); ok {
						publication.Publisher = publisher
					}
					if publishedOn, ok := pub["publishedOn"].(string); ok {
						publication.PublishedOn = publishedOn
					}
					if url, ok := pub["url"].(string); ok {
						publication.URL = url
					}
					if description, ok := pub["description"].(string); ok {
						publication.Description = description
					}
					
					user.Publications = append(user.Publications, publication)
				}
			}
		}
	}

	// Extract patents
	if patents, ok := profileData["patentView"].(map[string]interface{}); ok {
		if elements, ok := patents["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if pat, ok := elem.(map[string]interface{}); ok {
					patent := models.Patent{
						UserID: user.ID,
					}
					
					if title, ok := pat["title"].(string); ok {
						patent.Title = title
					}
					if patentOffice, ok := pat["patentOffice"].(string); ok {
						patent.PatentOffice = patentOffice
					}
					if patentNumber, ok := pat["patentNumber"].(string); ok {
						patent.PatentNumber = patentNumber
					}
					if url, ok := pat["url"].(string); ok {
						patent.URL = url
					}
					if issuedOn, ok := pat["issuedOn"].(string); ok {
						patent.IssuedOn = issuedOn
					}
					if description, ok := pat["description"].(string); ok {
						patent.Description = description
					}
					
					user.Patents = append(user.Patents, patent)
				}
			}
		}
	}

	// Extract volunteer experiences
	if volunteer, ok := profileData["volunteerExperienceView"].(map[string]interface{}); ok {
		if elements, ok := volunteer["elements"].([]interface{}); ok {
			for _, elem := range elements {
				if vol, ok := elem.(map[string]interface{}); ok {
					volunteerExp := models.VolunteerExperience{
						UserID: user.ID,
					}
					
					if role, ok := vol["role"].(string); ok {
						volunteerExp.Role = role
					}
					if organization, ok := vol["organization"].(string); ok {
						volunteerExp.Organization = organization
					}
					if cause, ok := vol["cause"].(string); ok {
						volunteerExp.Cause = cause
					}
					if description, ok := vol["description"].(string); ok {
						volunteerExp.Description = description
					}
					
					// Extract time period
					if timePeriod, ok := vol["timePeriod"].(map[string]interface{}); ok {
						if startDate, ok := timePeriod["startDate"].(map[string]interface{}); ok {
							if year, ok := startDate["year"].(float64); ok {
								volunteerExp.StartYear = intPtr(int(year))
							}
							if month, ok := startDate["month"].(float64); ok {
								volunteerExp.StartMonth = intPtr(int(month))
							}
						}
						if endDate, ok := timePeriod["endDate"].(map[string]interface{}); ok {
							if year, ok := endDate["year"].(float64); ok {
								volunteerExp.EndYear = intPtr(int(year))
							}
							if month, ok := endDate["month"].(float64); ok {
								volunteerExp.EndMonth = intPtr(int(month))
							}
						}
					}
					
					user.VolunteerExperiences = append(user.VolunteerExperiences, volunteerExp)
				}
			}
		}
	}

	return user, nil
}

// buildProfileExtractionScript returns JavaScript to extract LinkedIn profile data
func (s *LinkedInUserScraper) buildProfileExtractionScript() string {
	return `
		(function() {
			try {
				console.log('🔍 Starting profile data extraction...');
				
				// Initialize data structure
				var profileData = {
					profile: {},
					miniProfile: {},
					positionView: { elements: [] },
					educationView: { elements: [] },
					certificationView: { elements: [] },
					projectView: { elements: [] },
					publicationView: { elements: [] },
					patentView: { elements: [] },
					volunteerExperienceView: { elements: [] }
				};
				
				// Method 1: Extract from LinkedIn's require.js modules (most reliable)
				console.log('🔍 Method 1: Searching require.js modules...');
				if (window.require && window.require.s && window.require.s.contexts) {
					var contexts = window.require.s.contexts;
					var moduleCount = 0;
					for (var contextId in contexts) {
						try {
							var modules = contexts[contextId].defined;
							for (var moduleId in modules) {
								moduleCount++;
								var module = modules[moduleId];
								if (module && typeof module === 'object') {
									// Profile data
									if (module.profile && (module.profile.entityUrn || module.profile.firstName)) {
										console.log('✅ Found profile data in module:', moduleId);
										Object.assign(profileData.profile, module.profile);
									}
									if (module.miniProfile && module.miniProfile.publicIdentifier) {
										console.log('✅ Found miniProfile data in module:', moduleId);
										Object.assign(profileData.miniProfile, module.miniProfile);
									}
									// Experience data
									if (module.positionView && Array.isArray(module.positionView.elements)) {
										console.log('✅ Found', module.positionView.elements.length, 'positions in module:', moduleId);
										profileData.positionView = module.positionView;
									}
									// Education data
									if (module.educationView && Array.isArray(module.educationView.elements)) {
										console.log('✅ Found', module.educationView.elements.length, 'education entries in module:', moduleId);
										profileData.educationView = module.educationView;
									}
									// Other sections
									if (module.certificationView && Array.isArray(module.certificationView.elements)) {
										profileData.certificationView = module.certificationView;
									}
									if (module.projectView && Array.isArray(module.projectView.elements)) {
										profileData.projectView = module.projectView;
									}
									if (module.publicationView && Array.isArray(module.publicationView.elements)) {
										profileData.publicationView = module.publicationView;
									}
									if (module.patentView && Array.isArray(module.patentView.elements)) {
										profileData.patentView = module.patentView;
									}
									if (module.volunteerExperienceView && Array.isArray(module.volunteerExperienceView.elements)) {
										profileData.volunteerExperienceView = module.volunteerExperienceView;
									}
								}
							}
						} catch (e) {
							console.log('⚠️ Error processing module context:', contextId, e.message);
						}
					}
					console.log('📊 Total modules searched:', moduleCount);
				}
				
				// Method 2: Extract from DOM (fallback and supplement)
				console.log('🔍 Method 2: Extracting from DOM...');
				
				// Function to expand "see more" content
				function expandSeeMoreContent() {
					var seeMoreButtons = document.querySelectorAll(
						'[data-view-name="profile-card"] .inline-show-more-text__button, ' +
						'button[aria-expanded="false"], ' +
						'button[data-control-name*="see_more"], ' +
						'.show-more-less-html__button--more'
					);
					
					if (seeMoreButtons.length > 0) {
						console.log('🔍 Found', seeMoreButtons.length, '"see more" buttons, clicking...');
						for (var i = 0; i < seeMoreButtons.length; i++) {
							try {
								seeMoreButtons[i].click();
								console.log('✅ Clicked see more button', i + 1);
							} catch (e) {
								console.log('⚠️ Failed to click see more button:', e.message);
							}
						}
						return true;
					}
					return false;
				}
				
				// Function to expand "see more" content within current page (no navigation)
				function expandSeeMoreContent() {
					var seeMoreButtons = document.querySelectorAll(
						'.inline-show-more-text__button, ' +
						'button[aria-expanded="false"], ' +
						'button[data-control-name*="see_more"], ' +
						'.show-more-less-html__button--more'
					);
					
					console.log('🔍 Found', seeMoreButtons.length, '"see more" buttons to expand content');
					var expandedCount = 0;
					for (var i = 0; i < seeMoreButtons.length; i++) {
						try {
							// Only click buttons that expand content, not navigate to new pages
							var btn = seeMoreButtons[i];
							if (!btn.href && !btn.onclick && btn.getAttribute('aria-expanded') === 'false') {
								btn.click();
								expandedCount++;
								console.log('✅ Expanded content button', i + 1);
							}
						} catch (e) {
							console.log('⚠️ Failed to click expand button:', e.message);
						}
					}
					console.log('📊 Expanded', expandedCount, 'content sections');
					return expandedCount > 0;
				}
				
				// Try to expand content on current page only
				var expanded = expandSeeMoreContent();
				if (expanded) {
					console.log('⏳ Waiting for expanded content...');
					// Small delay for content to expand
					var start = Date.now();
					while (Date.now() - start < 1000) {
						// Busy wait for 1 second
					}
				}
				
				// Extract basic profile info from DOM
				function extractFromDOM() {
					// Name
					var nameSelectors = [
						'h1.text-heading-xlarge',
						'h1.text-heading-large', 
						'h1.pv-text-details__left-panel',
						'.pv-top-card--list h1',
						'[data-view-name="profile-card"] h1'
					];
					
					// Headline  
					var headlineSelectors = [
						'.text-body-medium.break-words',
						'.pv-text-details__left-panel .text-body-medium',
						'.pv-top-card--list .text-body-medium',
						'[data-view-name="profile-card"] .text-body-medium'
					];
					
					// Location
					var locationSelectors = [
						'.text-body-small.inline.t-black--light.break-words',
						'.pv-text-details__left-panel .text-body-small',
						'.pv-top-card--list .text-body-small',
						'[data-view-name="profile-card"] .text-body-small'
					];
					
					// Summary - now with better selectors
					var summarySelectors = [
						'[data-view-name="profile-card"] .display-flex.ph5.pv3 span[aria-hidden="true"]',
						'[data-view-name="profile-card"] .pv-shared-text-with-see-more span[aria-hidden="true"]',
						'[data-view-name="profile-card"] .inline-show-more-text span[aria-hidden="true"]',
						'[data-view-name="profile-card"] .text-body-medium span',
						'#about .pv-shared-text-with-see-more .text-body-medium',
						'.pv-about__summary-text .text-body-medium'
					];
					
					// Avatar
					var avatarSelectors = [
						'.pv-top-card__photo img',
						'.profile-photo-edit__preview img',
						'[data-view-name="profile-card"] img'
					];
					
					function getTextBySelectors(selectors) {
						for (var i = 0; i < selectors.length; i++) {
							var element = document.querySelector(selectors[i]);
							if (element && element.textContent && element.textContent.trim()) {
								console.log('✅ Found text with selector:', selectors[i]);
								return element.textContent.trim();
							}
						}
						return '';
					}
					
					function getImageBySelectors(selectors) {
						for (var i = 0; i < selectors.length; i++) {
							var element = document.querySelector(selectors[i]);
							if (element && element.src) {
								console.log('✅ Found image with selector:', selectors[i]);
								return element.src.split('?')[0]; // Remove query parameters
							}
						}
						return '';
					}
					
					// Fill in missing data from DOM
					if (!profileData.profile.firstName) {
						var fullName = getTextBySelectors(nameSelectors);
						if (fullName) {
							var nameParts = fullName.split(' ');
							profileData.profile.firstName = nameParts[0] || '';
							profileData.profile.lastName = nameParts.slice(1).join(' ') || '';
							console.log('✅ Extracted name from DOM:', fullName);
						}
					}
					
					if (!profileData.profile.headline) {
						profileData.profile.headline = getTextBySelectors(headlineSelectors);
						if (profileData.profile.headline) {
							console.log('✅ Extracted headline from DOM:', profileData.profile.headline.substring(0, 50) + '...');
						}
					}
					
					if (!profileData.profile.geoLocationName) {
						profileData.profile.geoLocationName = getTextBySelectors(locationSelectors);
						if (profileData.profile.geoLocationName) {
							console.log('✅ Extracted location from DOM:', profileData.profile.geoLocationName);
						}
					}
					
					if (!profileData.profile.summary) {
						profileData.profile.summary = getTextBySelectors(summarySelectors);
						if (profileData.profile.summary) {
							console.log('✅ Extracted summary from DOM (length:', profileData.profile.summary.length + ')');
						}
					}
					
					if (!profileData.miniProfile.picture) {
						var avatarUrl = getImageBySelectors(avatarSelectors);
						if (avatarUrl) {
							profileData.miniProfile.picture = { rootUrl: avatarUrl };
							console.log('✅ Extracted avatar from DOM');
						}
					}
					
					// Extract username from URL if missing
					if (!profileData.miniProfile.publicIdentifier) {
						var pathname = window.location.pathname;
						var match = pathname.match(/\/in\/([^\/]+)/);
						if (match) {
							profileData.miniProfile.publicIdentifier = match[1];
							console.log('✅ Extracted username from URL:', match[1]);
						}
					}
				}
				
				// Run DOM extraction
				extractFromDOM();
				
				// Extract skills for positions and education BEFORE navigating away
				console.log('🔍 Starting skill extraction for positions and education...');
				
				// Function to extract skills from a specific item (position or education)
				function extractSkillsForItem(item, itemType) {
					var skills = [];
					
					// Look for skill button based on the HTML structure provided
					var skillButtonSelectors = [
						'a[data-field*="contextual_skills_see_details"]',
						'a[data-field*="skill"]',
						'a[href*="skill-associations-details"]',
						'a[href*="skill-associations"]',
						'a[class*="optional-action-target-wrapper"][href*="overlay"]',
						'a[href*="overlay"][href*="skill"]',
						'a[class*="optional-action-target-wrapper"][href*="skill"]',
						// Look for the specific structure with skills icon
						'a[class*="optional-action-target-wrapper"] svg[data-test-icon="skills-small"]',
						// Look for parent of skills icon
						'a:has(svg[data-test-icon="skills-small"])',
						// More general selectors
						'a[href*="skill"]',
						'button[aria-label*="skill"], a[aria-label*="skill"]'
					];
					
					var skillButton = null;
					for (var i = 0; i < skillButtonSelectors.length; i++) {
						var selector = skillButtonSelectors[i];
						try {
							skillButton = item.querySelector(selector);
							if (skillButton) {
								console.log('✅ Found skill button for', itemType, 'with selector:', selector);
								break;
							}
						} catch (e) {
							// Some selectors might not work in all browsers, continue
							console.log('⚠️ Selector failed:', selector, e.message);
						}
					}
					
					// Alternative approach: look for skills icon first, then find parent link
					if (!skillButton) {
						var skillsIcon = item.querySelector('svg[data-test-icon="skills-small"]');
						if (skillsIcon) {
							// Find parent link element
							var parent = skillsIcon.parentElement;
							while (parent && parent.tagName !== 'A') {
								parent = parent.parentElement;
								if (!parent || parent === document.body) break;
							}
							if (parent && parent.tagName === 'A') {
								skillButton = parent;
								console.log('✅ Found skill button via icon for', itemType);
							}
						}
					}
					
					if (!skillButton) {
						console.log('ℹ️ No skill button found for', itemType);
						
						// Debug: Let's see what links are available in this item
						var allLinks = item.querySelectorAll('a');
						console.log('🔍 Debug: Found', allLinks.length, 'links in', itemType);
						for (var j = 0; j < Math.min(allLinks.length, 5); j++) {
							var link = allLinks[j];
							var href = link.href || 'no-href';
							var dataField = link.getAttribute('data-field') || 'no-data-field';
							var className = link.className || 'no-class';
							console.log('🔗 Link', j + ':', 'href=' + href.substring(0, 100), 'data-field=' + dataField, 'class=' + className.substring(0, 50));
						}
						
						// Debug: Check for skills-related text or icons
						var allElements = item.querySelectorAll('*');
						for (var k = 0; k < allElements.length; k++) {
							var elem = allElements[k];
							var text = elem.textContent || '';
							if (text.toLowerCase().includes('kompetenc') || text.toLowerCase().includes('skill') || 
							    text.toLowerCase().includes('laravel') || text.toLowerCase().includes('php')) {
								console.log('🔍 Found skills-related text:', text.substring(0, 50), 'in element:', elem.tagName);
								break;
							}
						}
						
						return skills;
					}
					
					// Click the skill button to open modal
					try {
						console.log('🔍 Clicking skill button for', itemType + '...');
						skillButton.click();
						
						// Wait for modal to appear
						var modalWaitStart = Date.now();
						var modal = null;
						while (Date.now() - modalWaitStart < 5000) { // Wait up to 5 seconds
							modal = document.querySelector('[data-test-modal], .artdeco-modal, [role="dialog"]');
							if (modal && modal.offsetParent !== null) { // Check if modal is visible
								console.log('✅ Modal opened for', itemType);
								break;
							}
							// Small delay
							var busyWait = Date.now();
							while (Date.now() - busyWait < 100) {}
						}
						
						if (!modal || modal.offsetParent === null) {
							console.log('⚠️ Modal did not appear for', itemType);
							return skills;
						}
						
						// Extract skills from modal content
						var skillSelectors = [
							'.artdeco-modal__content .display-flex.align-items-center.mr1.t-bold span[aria-hidden="true"]',
							'.artdeco-modal__content span[aria-hidden="true"]',
							'.artdeco-modal__content .t-bold span',
							'[role="dialog"] .t-bold span[aria-hidden="true"]'
						];
						
						var skillElements = [];
						for (var selector of skillSelectors) {
							var elements = modal.querySelectorAll(selector);
							if (elements.length > 0) {
								skillElements = elements;
								console.log('✅ Found', elements.length, 'skill elements with selector:', selector);
								break;
							}
						}
						
						// Extract skill names
						for (var i = 0; i < skillElements.length; i++) {
							var skillText = skillElements[i].textContent.trim();
							
							// Validate skill text (should be short skill name, not long description)
							if (skillText && skillText.length > 1 && skillText.length < 100 && 
							    !skillText.toLowerCase().includes('kompetencer') &&
							    !skillText.toLowerCase().includes('skills') &&
							    !skillText.toLowerCase().includes('læs mere') &&
							    !skillText.toLowerCase().includes('find job') &&
							    !skillText.toLowerCase().includes('udvid detaljer')) {
								
								skills.push(skillText);
								console.log('✅ Extracted skill:', skillText);
							}
						}
						
						console.log('📊 Extracted', skills.length, 'skills for', itemType);
						
						// Close modal
						var closeButton = modal.querySelector('[data-test-modal-close-btn], .artdeco-modal__dismiss, [aria-label*="fvis"], [aria-label*="lose"], button[class*="dismiss"]');
						if (closeButton) {
							closeButton.click();
							console.log('✅ Closed modal for', itemType);
							
							// Wait for modal to close
							var closeWaitStart = Date.now();
							while (Date.now() - closeWaitStart < 2000) {
								if (!modal.offsetParent || modal.offsetParent === null) {
									break;
								}
								var busyWait = Date.now();
								while (Date.now() - busyWait < 100) {}
							}
						}
						
					} catch (error) {
						console.log('❌ Error extracting skills for', itemType + ':', error.message);
					}
					
					return skills;
				}
				
				// Look for positions and education on the current page (before navigating)
				var currentPositions = document.querySelectorAll('.pvs-list__item--line-separated');
				console.log('🔍 Found', currentPositions.length, 'potential position/education items on current page');
				
				// Try to extract skills from positions before we navigate away
				for (var i = 0; i < Math.min(currentPositions.length, 8); i++) {
					var item = currentPositions[i];
					
					// Check if this looks like a position or education
					var itemText = item.textContent.toLowerCase();
					var isPosition = !itemText.includes('university') && !itemText.includes('college') && 
					                !itemText.includes('degree') && !itemText.includes('universitet') && 
					                !itemText.includes('uddannelse');
					
					if (isPosition) {
						// Try to extract title to identify which position this is
						var titleElement = item.querySelector('.hoverable-link-text.t-bold span[aria-hidden="true"]');
						var title = titleElement ? titleElement.textContent.trim() : 'Unknown Position';
						
						console.log('🔍 Trying to extract skills for position:', title);
						var skills = extractSkillsForItem(item, 'position "' + title + '"');
						
						if (skills.length > 0) {
							// Store skills with the item for later matching
							item.setAttribute('data-extracted-skills', JSON.stringify(skills));
							console.log('✅ Stored', skills.length, 'skills for position:', title);
						}
					} else {
						// This looks like education
						var schoolElement = item.querySelector('span[aria-hidden="true"]');
						var school = schoolElement ? schoolElement.textContent.trim() : 'Unknown School';
						
						console.log('🔍 Trying to extract skills for education:', school);
						var skills = extractSkillsForItem(item, 'education "' + school + '"');
						
						if (skills.length > 0) {
							// Store skills with the item for later matching
							item.setAttribute('data-extracted-skills', JSON.stringify(skills));
							console.log('✅ Stored', skills.length, 'skills for education:', school);
						}
					}
				}
				
				// Method 3: Extract positions/experience from DOM if not found in modules
				if (profileData.positionView.elements.length === 0) {
					console.log('🔍 Extracting positions from DOM...');
					
					// Skip clicking "see all experiences" to avoid navigation issues
					// Instead, work with the visible content on the main profile page
					console.log('ℹ️ Working with visible experience content on main profile page');
					
					// Debug: Let's see what sections are available
					var allSections = document.querySelectorAll('[data-view-name*="profile"], section, .pv-profile-section, [id*="experience"], [id*="education"]');
					console.log('🔍 Found', allSections.length, 'potential profile sections');
					for (var i = 0; i < Math.min(allSections.length, 10); i++) {
						var section = allSections[i];
						var id = section.id || section.getAttribute('data-view-name') || section.className;
						console.log('📋 Section', i + ':', id);
					}
					
					// Try multiple strategies to find experience
					var experienceStrategies = [
						// Strategy 1: Look for experience section by ID or heading
						function() {
							var experienceSection = document.querySelector('#experience');
							if (experienceSection) {
								console.log('✅ Found #experience section');
								// Look for elements in the next few siblings
								var container = experienceSection.parentElement;
								var elements = container.querySelectorAll('.pvs-list__item--line-separated, .pvs-entity, div[data-view-name*="profile"]');
								console.log('📋 Found', elements.length, 'potential experience items in container');
								return elements;
							}
							return null;
						},
						
						// Strategy 2: Look for "Experience" heading
						function() {
							var headings = document.querySelectorAll('h2, h3, .text-heading-large, .text-heading-medium');
							for (var i = 0; i < headings.length; i++) {
								if (headings[i].textContent.trim().toLowerCase().includes('experience') || 
								    headings[i].textContent.trim().toLowerCase().includes('erfaring')) {
									console.log('✅ Found experience heading:', headings[i].textContent.trim());
									// Look in the entire document for list items
									var allItems = document.querySelectorAll('.pvs-list__item--line-separated, .pvs-entity, div[class*="pvs-list"]');
									console.log('📋 Found', allItems.length, 'total list items in document');
									return allItems;
								}
							}
							return null;
						},
						
						// Strategy 3: Look for any profile component entities (new LinkedIn layout)
						function() {
							var elements = document.querySelectorAll('.pvs-list__item--line-separated');
							if (elements.length > 0) {
								console.log('✅ Found', elements.length, 'potential experience items');
								// Filter for experience-like items (exclude education, skills, etc.)
								var filtered = [];
								for (var i = 0; i < elements.length; i++) {
									var elem = elements[i];
									var text = elem.textContent.toLowerCase();
									// Skip if it looks like education, skills, or certifications
									if (!text.includes('university') && !text.includes('college') && 
									    !text.includes('degree') && !text.includes('certification') &&
									    !text.includes('universitet') && !text.includes('uddannelse')) {
										filtered.push(elem);
									}
								}
								console.log('✅ Filtered to', filtered.length, 'potential experience items');
								return filtered;
							}
							return null;
						}
					];
					
					var experienceElements = null;
					for (var strategyIndex = 0; strategyIndex < experienceStrategies.length; strategyIndex++) {
						experienceElements = experienceStrategies[strategyIndex]();
						if (experienceElements && experienceElements.length > 0) {
							console.log('✅ Strategy', strategyIndex + 1, 'found', experienceElements.length, 'experience elements');
							break;
						}
					}
					
					if (experienceElements && experienceElements.length > 0) {
						for (var i = 0; i < Math.min(experienceElements.length, 10); i++) { // Limit to 10 items
							var elem = experienceElements[i];
							var position = { timePeriod: {} };
							
							// First, try to expand any "see more" buttons in this position
							var seeMoreBtns = elem.querySelectorAll('.inline-show-more-text__button, button[aria-expanded="false"]');
							if (seeMoreBtns.length > 0) {
								console.log('🔍 Found', seeMoreBtns.length, '"see more" buttons in position, clicking...');
								for (var btnI = 0; btnI < seeMoreBtns.length; btnI++) {
									try {
										seeMoreBtns[btnI].click();
										console.log('✅ Clicked position "see more" button', btnI + 1);
									} catch (e) {
										console.log('⚠️ Failed to click position "see more" button:', e.message);
									}
								}
								// Small wait for content to expand
								var expandWait = Date.now();
								while (Date.now() - expandWait < 500) {
									// Wait 500ms
								}
							}
							
							// Extract job title - look for the title span specifically
							var titleSelectors = [
								'div.mr1.hoverable-link-text.t-bold span[aria-hidden="true"]',
								'.hoverable-link-text.t-bold span[aria-hidden="true"]',
								'.t-bold span[aria-hidden="true"]',
								'span[aria-hidden="true"]'
							];
							
							for (var selectorIndex = 0; selectorIndex < titleSelectors.length; selectorIndex++) {
								var titleElement = elem.querySelector(titleSelectors[selectorIndex]);
								if (titleElement) {
									var titleText = titleElement.textContent.trim();
									console.log('🔍 Checking title text:', titleText, '(length:', titleText.length, ')');
									
									// Validate this looks like a job title, not a description
									if (titleText && titleText.length > 2 && titleText.length < 150 && 
									    !titleText.toLowerCase().includes('responsible for') &&
									    !titleText.toLowerCase().includes('focus on') &&
									    !titleText.toLowerCase().includes('as a specialist') &&
									    !titleText.includes('\n')) {
										position.title = titleText;
										console.log('✅ Found title:', titleText);
										break;
									} else {
										console.log('⚠️ Skipping - looks like description or too long');
									}
								}
							}
							
							// Extract company name and employment type - look for the specific structure
							var companySelectors = [
								'span.t-14.t-normal span[aria-hidden="true"]',
								'.t-14.t-normal:not(.t-black--light) span[aria-hidden="true"]',
								'div:nth-child(2) span[aria-hidden="true"]'
							];
							
							for (var selectorIndex = 0; selectorIndex < companySelectors.length; selectorIndex++) {
								var companySpan = elem.querySelector(companySelectors[selectorIndex]);
								if (companySpan) {
									var companyText = companySpan.textContent.trim();
									console.log('🔍 Checking company text:', companyText);
									
									// Skip if this looks like a job description or title
									if (companyText.length > 200 || companyText.toLowerCase().includes('responsible for') || 
									    companyText.toLowerCase().includes('focus on') || companyText.toLowerCase().includes('as a ')) {
										console.log('⚠️ Skipping - looks like description, not company');
										continue;
									}
									
									// Company name is usually before the "·" separator
									var companyParts = companyText.split('·');
									if (companyParts.length > 0 && companyParts[0].trim().length > 2) {
										position.companyName = companyParts[0].trim();
										console.log('✅ Found company:', position.companyName);
										break;
									}
								}
							}
							
							// Extract dates - look for the time period span
							var dateSpan = elem.querySelector('span.pvs-entity__caption-wrapper[aria-hidden="true"]');
							if (!dateSpan) {
								dateSpan = elem.querySelector('span.t-black--light span[aria-hidden="true"]');
							}
							if (dateSpan) {
								var dateText = dateSpan.textContent.trim();
								console.log('🔍 Parsing date text:', dateText);
								
								// Handle different date formats: "sep. 2024 - I dag", "jan. 2023 - dec. 2023", etc.
								var dateMatch = dateText.match(/(\w+\.?)\s+(\d{4})\s*[-–]\s*(?:(\w+\.?)\s+(\d{4})|I dag|Present|Nu)/i);
								if (dateMatch) {
									position.timePeriod.startDate = {
										month: getMonthNumber(dateMatch[1].replace('.', '')),
										year: parseInt(dateMatch[2])
									};
									if (dateMatch[3] && dateMatch[4]) {
										position.timePeriod.endDate = {
											month: getMonthNumber(dateMatch[3].replace('.', '')),
											year: parseInt(dateMatch[4])
										};
									}
									// If "I dag", "Present", or "Nu" is present, it's current position (no end date)
									console.log('✅ Parsed dates - Start:', dateMatch[1], dateMatch[2], 'End:', dateMatch[3] || 'Current');
								}
							}
							
							// Extract job description - look for the description text (after expanding "see more")
							var descriptionSelectors = [
								'.inline-show-more-text span[aria-hidden="true"]',
								'.pvs-list__outer-container .inline-show-more-text span[aria-hidden="true"]',
								'.doZPbGfCnSqpkLeVxBmoidcUVOUmxDGmstUw span[aria-hidden="true"]',
								'.t-14.t-normal.t-black span[aria-hidden="true"]',
								'.pvs-entity__sub-components span[aria-hidden="true"]'
							];
							
							for (var selectorIndex = 0; selectorIndex < descriptionSelectors.length; selectorIndex++) {
								var descriptionElement = elem.querySelector(descriptionSelectors[selectorIndex]);
								if (descriptionElement) {
									var descriptionText = descriptionElement.textContent.trim();
									console.log('🔍 Checking description text (length:', descriptionText.length, '):', descriptionText.substring(0, 50) + '...');
									
									// Only include if it looks like a proper description (longer text)
									// and doesn't look like a title, company name, or date
									if (descriptionText && descriptionText.length > 50 && 
									    !descriptionText.includes('·') && // Avoid company/employment type strings
									    !descriptionText.match(/^\w+\.?\s+\d{4}/)) { // Avoid date strings
										position.description = descriptionText;
										console.log('✅ Found description (length:', descriptionText.length, ')');
										break;
									} else {
										console.log('⚠️ Skipping - not a valid description');
									}
								}
							}
							
							// Extract location - look for location span
							var locationSpans = elem.querySelectorAll('span.t-black--light span[aria-hidden="true"]');
							for (var j = 0; j < locationSpans.length; j++) {
								var locationText = locationSpans[j].textContent.trim();
								if (locationText.includes('Region') || locationText.includes('Danmark') || 
								    locationText.includes('Copenhagen') || locationText.includes('København') ||
								    locationText.includes('Remote') || locationText.includes('arbejdsstedet')) {
									position.location = locationText.split('·')[0].trim();
									break;
								}
							}
							
							// Only add if we have meaningful data
							if (position.title && position.title.length > 2) {
								profileData.positionView.elements.push(position);
								console.log('✅ Added position:', position.title, 'at', position.companyName || 'Unknown company');
								if (position.description) {
									console.log('   Description length:', position.description.length, 'chars');
								}
								if (position.timePeriod.startDate) {
									console.log('   Start date:', position.timePeriod.startDate.month + '/' + position.timePeriod.startDate.year);
								}
							}
						}
					} else {
						console.log('❌ No experience elements found with any strategy');
					}
				}
				
				// Method 4: Extract education from DOM if not found in modules
				if (profileData.educationView.elements.length === 0) {
					console.log('🔍 Extracting education from DOM...');
					
					// Skip clicking "see all education" to avoid navigation issues
					// Instead, work with the visible content on the main profile page
					console.log('ℹ️ Working with visible education content on main profile page');
					
					// Try multiple strategies to find education
					var educationStrategies = [
						// Strategy 1: Look for education section by ID or heading
						function() {
							var educationSection = document.querySelector('#education');
							if (educationSection) {
								console.log('✅ Found #education section');
								// Look for elements in the next few siblings
								var container = educationSection.parentElement;
								var elements = container.querySelectorAll('.pvs-list__item--line-separated, .pvs-entity, div[data-view-name*="profile"]');
								console.log('📋 Found', elements.length, 'potential education items in container');
								return elements;
							}
							return null;
						},
						
						// Strategy 2: Look for "Education" heading
						function() {
							var headings = document.querySelectorAll('h2, h3, .text-heading-large, .text-heading-medium');
							for (var i = 0; i < headings.length; i++) {
								if (headings[i].textContent.trim().toLowerCase().includes('education') || 
								    headings[i].textContent.trim().toLowerCase().includes('uddannelse')) {
									console.log('✅ Found education heading:', headings[i].textContent.trim());
									// Look in the entire document for list items
									var allItems = document.querySelectorAll('.pvs-list__item--line-separated, .pvs-entity, div[class*="pvs-list"]');
									console.log('📋 Found', allItems.length, 'total list items in document');
									return allItems;
								}
							}
							return null;
						},
						
						// Strategy 3: Look for education-like content
						function() {
							var elements = document.querySelectorAll('.pvs-list__item--line-separated');
							if (elements.length > 0) {
								console.log('✅ Filtering for education from', elements.length, 'items');
								var filtered = [];
								for (var i = 0; i < elements.length; i++) {
									var elem = elements[i];
									var text = elem.textContent.toLowerCase();
									// Include if it looks like education
									if (text.includes('university') || text.includes('college') || 
									    text.includes('degree') || text.includes('bachelor') || text.includes('master') ||
									    text.includes('universitet') || text.includes('uddannelse') || text.includes('højskole')) {
										filtered.push(elem);
									}
								}
								console.log('✅ Filtered to', filtered.length, 'potential education items');
								return filtered;
							}
							return null;
						}
					];
					
					var educationElements = null;
					for (var strategyIndex = 0; strategyIndex < educationStrategies.length; strategyIndex++) {
						educationElements = educationStrategies[strategyIndex]();
						if (educationElements && educationElements.length > 0) {
							console.log('✅ Education strategy', strategyIndex + 1, 'found', educationElements.length, 'education elements');
							break;
						}
					}
					
					if (educationElements && educationElements.length > 0) {
						for (var i = 0; i < Math.min(educationElements.length, 10); i++) { // Limit to 10 items
							var elem = educationElements[i];
							var education = { timePeriod: {} };
							
							// First, try to expand any "see more" buttons in this education entry
							var seeMoreBtns = elem.querySelectorAll('.inline-show-more-text__button, button[aria-expanded="false"]');
							if (seeMoreBtns.length > 0) {
								console.log('🔍 Found', seeMoreBtns.length, '"see more" buttons in education, clicking...');
								for (var btnI = 0; btnI < seeMoreBtns.length; btnI++) {
									try {
										seeMoreBtns[btnI].click();
										console.log('✅ Clicked education "see more" button', btnI + 1);
									} catch (e) {
										console.log('⚠️ Failed to click education "see more" button:', e.message);
									}
								}
								// Small wait for content to expand
								var expandWait = Date.now();
								while (Date.now() - expandWait < 500) {
									// Wait 500ms
								}
							}
							
							// Enhanced school name extraction
							var schoolElement = elem.querySelector('span[aria-hidden="true"]');
							if (schoolElement) {
								var schoolText = schoolElement.textContent.trim();
								if (schoolText && schoolText.length > 2) {
									education.schoolName = schoolText;
								}
							}
							
							// Enhanced degree/field extraction - look for additional spans
							var allSpans = elem.querySelectorAll('span[aria-hidden="true"]');
							for (var j = 1; j < allSpans.length; j++) {
								var spanText = allSpans[j].textContent.trim();
								if (spanText && spanText.length > 2 && 
								    !spanText.match(/\d{4}|months?|years?|·/i) &&
								    spanText !== education.schoolName) {
									if (!education.degree) {
										education.degree = spanText;
									} else if (!education.fieldOfStudy && spanText.includes(',')) {
										var parts = spanText.split(',');
										education.fieldOfStudy = parts[1].trim();
									}
									break;
								}
							}
							
							// Enhanced date extraction for education
							for (var j = 0; j < allSpans.length; j++) {
								var spanText = allSpans[j].textContent.trim();
								var yearRange = spanText.match(/(\d{4})\s*[-–]\s*(\d{4})/);
								if (yearRange) {
									education.timePeriod.startDate = { year: parseInt(yearRange[1]) };
									education.timePeriod.endDate = { year: parseInt(yearRange[2]) };
									break;
								} else {
									var singleYear = spanText.match(/(\d{4})/);
									if (singleYear) {
										education.timePeriod.endDate = { year: parseInt(singleYear[1]) };
										break;
									}
								}
							}
							
							// Only add if we have meaningful data
							if (education.schoolName && education.schoolName.length > 2) {
								profileData.educationView.elements.push(education);
								console.log('✅ Added education:', education.schoolName, '-', education.degree || 'No degree');
							}
						}
					} else {
						console.log('❌ No education elements found with any strategy');
					}
				}
				
				// Extract skills for positions and education by collecting skill URLs
				console.log('🔍 Starting skill extraction for positions and education...');
				
				// Function to extract skill URL from a DOM element
				function getSkillUrlFromElement(element, itemType) {
					var skillLinkSelectors = [
						'a[data-field*="contextual_skills_see_details"]',
						'a[href*="skill-associations-details"]',
						'a[href*="skill-associations"]',
						'a[href*="overlay"][href*="skill"]'
					];
					
					for (var i = 0; i < skillLinkSelectors.length; i++) {
						var skillLink = element.querySelector(skillLinkSelectors[i]);
						if (skillLink && skillLink.href) {
							console.log('✅ Found skill URL for', itemType + ':', skillLink.href);
							return skillLink.href;
						}
					}
					
					console.log('ℹ️ No skill URL found for', itemType);
					return null;
				}
				
				// Collect skill URLs by scanning all visible position/education items
				var positionSkillUrls = [];
				var educationSkillUrls = [];
				
				// Find all profile items that might contain skills
				var allProfileItems = document.querySelectorAll('.pvs-list__item--line-separated, .pvs-entity, [data-view-name="profile-component-entity"]');
				console.log('🔍 Found', allProfileItems.length, 'potential profile items to scan for skills');
				
				for (var i = 0; i < allProfileItems.length; i++) {
					var item = allProfileItems[i];
					var itemText = item.textContent.toLowerCase();
					
					// Check if this item has a skill URL
					var skillUrl = getSkillUrlFromElement(item, 'item ' + i);
					if (!skillUrl) continue;
					
					// Try to identify what type of item this is and get its details
					var titleElement = item.querySelector('.hoverable-link-text.t-bold span[aria-hidden="true"], .t-bold span[aria-hidden="true"]');
					var title = titleElement ? titleElement.textContent.trim() : 'Unknown';
					
					// Determine if this is position or education based on content
					var isEducation = itemText.includes('university') || itemText.includes('college') || 
					                 itemText.includes('degree') || itemText.includes('bachelor') || 
					                 itemText.includes('master') || itemText.includes('universitet') || 
					                 itemText.includes('uddannelse') || itemText.includes('højskole');
					
					if (isEducation) {
						educationSkillUrls.push({
							url: skillUrl,
							title: title,
							type: 'education'
						});
						console.log('📚 Added education skill URL for:', title);
					} else {
						// Assume it's a position if it has skills and isn't clearly education
						positionSkillUrls.push({
							url: skillUrl,
							title: title,
							type: 'position'
						});
						console.log('💼 Added position skill URL for:', title);
					}
				}
				
				// Store the collected URLs in the profile data for Go to process
				profileData.skillUrls = {
					positions: positionSkillUrls,
					education: educationSkillUrls
				};
				
				console.log('📊 Collected', positionSkillUrls.length, 'position skill URLs and', educationSkillUrls.length, 'education skill URLs');
				
				// Helper function to convert month names to numbers
				function getMonthNumber(monthName) {
					var months = {
						'Jan': 1, 'January': 1, 'Feb': 2, 'February': 2, 'Mar': 3, 'March': 3,
						'Apr': 4, 'April': 4, 'May': 5, 'Jun': 6, 'June': 6,
						'Jul': 7, 'July': 7, 'Aug': 8, 'August': 8, 'Sep': 9, 'September': 9,
						'Oct': 10, 'October': 10, 'Nov': 11, 'November': 11, 'Dec': 12, 'December': 12
					};
					return months[monthName] || 1;
				}
				
				// Log final results
				console.log('✅ Profile extraction complete. Results:');
				console.log('- Name:', profileData.profile.firstName, profileData.profile.lastName);
				console.log('- Headline:', profileData.profile.headline ? 'Found (' + profileData.profile.headline.length + ' chars)' : 'Not found');
				console.log('- Location:', profileData.profile.geoLocationName || 'Not found');
				console.log('- Summary:', profileData.profile.summary ? 'Found (' + profileData.profile.summary.length + ' chars)' : 'Not found');
				console.log('- Username:', profileData.miniProfile.publicIdentifier || 'Not found');
				console.log('- Avatar:', profileData.miniProfile.picture ? 'Found' : 'Not found');
				console.log('- Positions:', profileData.positionView.elements.length);
				console.log('- Education:', profileData.educationView.elements.length);
				
				// Count total skills extracted
				var totalSkills = 0;
				var positionsWithSkills = 0;
				var educationWithSkills = 0;
				
				if (profileData.positionView && profileData.positionView.elements) {
					for (var i = 0; i < profileData.positionView.elements.length; i++) {
						if (profileData.positionView.elements[i].skills && profileData.positionView.elements[i].skills.length > 0) {
							positionsWithSkills++;
							totalSkills += profileData.positionView.elements[i].skills.length;
						}
					}
				}
				
				if (profileData.educationView && profileData.educationView.elements) {
					for (var i = 0; i < profileData.educationView.elements.length; i++) {
						if (profileData.educationView.elements[i].skills && profileData.educationView.elements[i].skills.length > 0) {
							educationWithSkills++;
							totalSkills += profileData.educationView.elements[i].skills.length;
						}
					}
				}
				
				console.log('- Skills:', totalSkills, 'total from', positionsWithSkills, 'positions and', educationWithSkills, 'education entries');
				
				return JSON.stringify(profileData);
				
			} catch (error) {
				console.log('❌ Error extracting profile data:', error);
				return JSON.stringify({
					error: error.message,
					profile: {},
					miniProfile: { 
						publicIdentifier: window.location.pathname.split('/in/')[1]?.replace('/', '') || '' 
					}
				});
			}
		})();
	`
}

// extractSkillsFromUrl navigates to a skill detail URL and extracts the skills
func (s *LinkedInUserScraper) extractSkillsFromUrl(ctx context.Context, skillUrl, itemDescription string) ([]string, error) {
	logrus.Debugf("🔍 Navigating to skill URL for %s: %s", itemDescription, skillUrl)
	
	// Store the current URL to return to it later
	var currentUrl string
	err := chromedp.Run(ctx,
		chromedp.Location(&currentUrl),
	)
	if err != nil {
		logrus.Warnf("⚠️ Could not get current URL: %v", err)
	}
	
	// Navigate to the skill detail URL
	err = chromedp.Run(ctx,
		chromedp.Navigate(skillUrl),
		chromedp.WaitVisible(`body`, chromedp.ByQuery),
		chromedp.Sleep(3*time.Second), // Wait for content to load
	)
	
	if err != nil {
		return nil, fmt.Errorf("failed to navigate to skill URL: %w", err)
	}
	
	// Extract skills from the skill detail page
	var skillsStr string
	skillExtractionScript := `
		(function() {
			try {
				console.log('🔍 Extracting skills from skill detail page...');
				
				var skills = [];
				
				// Multiple strategies to find skills on the detail page
				var skillSelectors = [
					// Main skill list in modal/overlay content
					'.artdeco-modal__content .display-flex.align-items-center.mr1.t-bold span[aria-hidden="true"]',
					'.artdeco-modal__content span[aria-hidden="true"]',
					'.artdeco-modal__content .t-bold span[aria-hidden="true"]',
					'[role="dialog"] .t-bold span[aria-hidden="true"]',
					// Alternative selectors for different layouts
					'.pvs-list .display-flex.align-items-center.mr1.t-bold span[aria-hidden="true"]',
					'.pvs-list .t-bold span[aria-hidden="true"]',
					// General skill item selectors
					'[data-view-name*="skill"] .t-bold span[aria-hidden="true"]',
					'.skill-item .t-bold span[aria-hidden="true"]',
					// Broader selectors
					'.t-bold span[aria-hidden="true"]'
				];
				
				// Try each selector strategy
				for (var i = 0; i < skillSelectors.length; i++) {
					var elements = document.querySelectorAll(skillSelectors[i]);
					console.log('🔍 Trying selector:', skillSelectors[i], '- found', elements.length, 'elements');
					
					if (elements.length > 0) {
						for (var j = 0; j < elements.length; j++) {
							var skillText = elements[j].textContent.trim();
							
							// Validate skill text (should be short skill name, not long description)
							if (skillText && skillText.length > 1 && skillText.length < 100 && 
							    !skillText.toLowerCase().includes('kompetencer') &&
							    !skillText.toLowerCase().includes('skills') &&
							    !skillText.toLowerCase().includes('læs mere') &&
							    !skillText.toLowerCase().includes('read more') &&
							    !skillText.toLowerCase().includes('find job') &&
							    !skillText.toLowerCase().includes('udvid detaljer') &&
							    !skillText.toLowerCase().includes('expand details') &&
							    !skillText.toLowerCase().includes('vis detaljer') &&
							    !skillText.toLowerCase().includes('show details') &&
							    !skillText.toLowerCase().includes('navigation') &&
							    !skillText.toLowerCase().includes('close') &&
							    !skillText.toLowerCase().includes('afvis') &&
							    !skillText.toLowerCase().includes('dismiss')) {
								
								// Avoid duplicates
								if (skills.indexOf(skillText) === -1) {
									skills.push(skillText);
									console.log('✅ Found skill:', skillText);
								}
							} else {
								console.log('⚠️ Skipped text (invalid):', skillText.substring(0, 50));
							}
						}
						
						if (skills.length > 0) {
							console.log('✅ Successfully extracted', skills.length, 'skills using selector:', skillSelectors[i]);
							break; // Found skills, no need to try other selectors
						}
					}
				}
				
				console.log('📊 Total skills extracted:', skills.length);
				return JSON.stringify(skills);
				
			} catch (error) {
				console.log('❌ Error extracting skills:', error.message);
				return JSON.stringify([]);
			}
		})();
	`
	
	err = chromedp.Run(ctx,
		chromedp.Evaluate(skillExtractionScript, &skillsStr),
	)
	
	if err != nil {
		return nil, fmt.Errorf("failed to extract skills: %w", err)
	}
	
	// Navigate back to the original page if we have the URL
	if currentUrl != "" {
		err = chromedp.Run(ctx,
			chromedp.Navigate(currentUrl),
			chromedp.Sleep(2*time.Second), // Wait for page to load
		)
		if err != nil {
			logrus.Warnf("⚠️ Could not navigate back to original page: %v", err)
		} else {
			logrus.Debugf("↩️ Returned to original page: %s", currentUrl)
		}
	}
	
	// Parse the skills JSON
	var skills []string
	if err := json.Unmarshal([]byte(skillsStr), &skills); err != nil {
		return nil, fmt.Errorf("failed to parse skills JSON: %w", err)
	}
	
	logrus.Debugf("✅ Extracted %d skills for %s: %v", len(skills), itemDescription, skills)
	
	return skills, nil
}

// Helper functions
func timePtr(t time.Time) *time.Time {
	return &t
}

func intPtr(i int) *int {
	return &i
}
