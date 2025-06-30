// skills.js - LinkedIn skills and work type extraction
const getWorkTypeAndSkills = function() {
    const result = { workType: '', skills: [] };
    
    console.log('=== SEARCHING FOR SKILLS AND WORK TYPE ===');
    
    // Check if skills modal is open
    const modalSelectors = [
        '.job-details-skill-match-modal',
        '[role="dialog"]',
        '[data-test-modal="job-details-skill-match-modal"]',
        '.artdeco-modal'
    ];
    
    let modal = null;
    for (const selector of modalSelectors) {
        modal = document.querySelector(selector);
        if (modal) {
            console.log('✅ Found modal with selector:', selector);
            break;
        } else {
            console.log('❌ No modal found with selector:', selector);
        }
    }
    
    if (modal) {
        console.log('=== EXTRACTING FROM SKILLS MODAL ===');
        
        // Extract work type from requirements
        const requirementsList = modal.querySelectorAll('.job-details-skill-match-modal__screening-questions-qualification-list-item, li[class*="qualification"]');
        console.log('Found', requirementsList.length, 'requirement items');
        
        for (const item of requirementsList) {
            const text = item.innerText.toLowerCase();
            console.log('Checking requirement:', text);
            
            if (text.includes('fjernarbejde') || text.includes('remote')) {
                result.workType = 'Remote';
                console.log('✅ Found work type: Remote');
            } else if (text.includes('hybridarbejde') || text.includes('hybrid')) {
                result.workType = 'Hybrid';
                console.log('✅ Found work type: Hybrid');
            } else if (text.includes('arbejder på arbejdspladsen') || text.includes('on-site') || text.includes('arbejdspladsen')) {
                result.workType = 'On-site';
                console.log('✅ Found work type: On-site');
            }
        }
        
        // Extract skills from both matched and unmatched lists
        const skillSelectors = [
            '.job-details-skill-match-status-list__matched-skill',
            '.job-details-skill-match-status-list__unmatched-skill',
            'li[class*="skill"]',
            '[class*="skill-match"]'
        ];
        
        let skillElements = [];
        for (const selector of skillSelectors) {
            const elements = modal.querySelectorAll(selector);
            skillElements = skillElements.concat(Array.from(elements));
        }
        
        console.log('Found', skillElements.length, 'skill elements');
        
        for (const skillEl of skillElements) {
            // Look for the skill name in aria-label or text content
            const ariaLabel = skillEl.getAttribute('aria-label');
            if (ariaLabel) {
                // Extract skill name from aria-label like "Din profil viser, at du har C# som en kompetence"
                const skillMatch = ariaLabel.match(/(?:har|viser ikke)\s+([^.]+?)\s+som en kompetence/i);
                if (skillMatch) {
                    const skillName = skillMatch[1].trim();
                    if (skillName && !result.skills.includes(skillName)) {
                        result.skills.push(skillName);
                        console.log('✅ Found skill from aria-label:', skillName);
                    }
                }
            }
            
            // Also try direct text content as fallback
            const skillTextEl = skillEl.querySelector('div[aria-label] div, .job-details-skill-match-status-list__skill-name, div');
            if (skillTextEl && skillTextEl.innerText) {
                const skillName = skillTextEl.innerText.trim();
                if (skillName && !result.skills.includes(skillName) && skillName.length > 0 && skillName.length < 50) {
                    result.skills.push(skillName);
                    console.log('✅ Found skill from text:', skillName);
                }
            }
        }
    } else {
        console.log('⚠️  No skills modal found, trying alternative methods...');
        
        // Try to extract work type from job description or other elements
        const descriptionSelectors = [
            '.jobs-description-content__text',
            '.jobs-description__content', 
            '.job-details-jobs-unified-top-card__job-description',
            '.description__text',
            'div.description',
            '[data-max-lines] .jobs-description-content__text',
            '.show-more-less-html__markup'
        ];
        
        let foundDescription = false;
        for (const selector of descriptionSelectors) {
            const desc = document.querySelector(selector);
            if (desc && desc.innerText && desc.innerText.length > 50) {
                const text = desc.innerText.toLowerCase();
                console.log('Checking description for work type (', text.length, 'chars)...');
                foundDescription = true;
                
                // More comprehensive work type detection
                const workTypePatterns = {
                    remote: [
                        'remote', 'fjernarbejde', 'hjemmefra', 'work from home', 'fully remote', 
                        'helt hjemmefra', '100% remote', 'remotely', 'work remotely', 'home office',
                        'hjemmekontor', 'fjernarbej', 'remote work'
                    ],
                    hybrid: [
                        'hybrid', 'hybridarbejde', 'flexible', 'flexibel', 'delvis hjemmefra', 
                        'partly remote', 'mixed', 'blandet', 'fleksibel', 'både hjemme og kontor',
                        'kombineret', 'combined'
                    ],
                    onsite: [
                        'on-site', 'på kontoret', 'arbejdspladsen', 'office', 'kontor', 
                        'fysisk fremmøde', 'onsight', 'on site', 'in office', 'på arbejde',
                        'workplace', 'arbejdsplads', 'lokaler'
                    ]
                };
                
                // Check for remote patterns first (most specific)
                let workTypeFound = false;
                for (const pattern of workTypePatterns.remote) {
                    if (text.includes(pattern)) {
                        result.workType = 'Remote';
                        console.log('✅ Found Remote work type in description with pattern:', pattern);
                        workTypeFound = true;
                        break;
                    }
                }
                
                // If not remote, check for hybrid
                if (!workTypeFound) {
                    for (const pattern of workTypePatterns.hybrid) {
                        if (text.includes(pattern)) {
                            result.workType = 'Hybrid';
                            console.log('✅ Found Hybrid work type in description with pattern:', pattern);
                            workTypeFound = true;
                            break;
                        }
                    }
                }
                
                // If not remote or hybrid, check for on-site
                if (!workTypeFound) {
                    for (const pattern of workTypePatterns.onsite) {
                        if (text.includes(pattern)) {
                            result.workType = 'On-site';
                            console.log('✅ Found On-site work type in description with pattern:', pattern);
                            workTypeFound = true;
                            break;
                        }
                    }
                }
                
                if (!workTypeFound) {
                    console.log('⚠️ No work type patterns found in description');
                    console.log('First 500 chars of description for analysis:', text.substring(0, 500));
                }
                
                // Try to extract skills from description as well
                console.log('Trying to extract skills from description...');
                const skillPatterns = [
                    /\b(php|javascript|java|python|c#|c\+\+|react|angular|vue|node\.?js|typescript|go|rust|swift|kotlin|scala)\b/gi,
                    /\b(sql|mysql|postgresql|mongodb|redis|elasticsearch|docker|kubernetes|aws|azure|gcp)\b/gi,
                    /\b(html|css|scss|sass|bootstrap|tailwind|jquery|webpack|git)\b/gi,
                    /\b(rest|api|microservices|agile|scrum|devops|ci\/cd|jenkins|gitlab)\b/gi
                ];
                
                for (const pattern of skillPatterns) {
                    const matches = text.match(pattern);
                    if (matches) {
                        for (const match of matches) {
                            const skill = match.trim();
                            if (skill && skill.length > 1 && !result.skills.includes(skill)) {
                                result.skills.push(skill);
                                console.log('✅ Found skill in description:', skill);
                            }
                        }
                    }
                }
                
                break;
            }
        }
        
        if (!foundDescription) {
            console.log('❌ No suitable description found for work type/skills extraction');
        }
        
        // Try to find skills in other page elements
        const skillContainerSelectors = [
            '.job-details-jobs-unified-top-card__primary-description',
            '.jobs-unified-top-card__content',
            '.job-details-preferences-and-skills',
            '[class*="skill"]',
            '[class*="requirement"]'
        ];
        
        for (const selector of skillContainerSelectors) {
            const container = document.querySelector(selector);
            if (container && container.innerText) {
                const text = container.innerText.toLowerCase();
                console.log('Checking container for skills:', selector);
                
                // Extract technical skills
                const techSkills = text.match(/\b(php|javascript|java|python|c#|c\+\+|react|angular|vue|node\.?js|typescript|go|rust|swift|kotlin|scala|sql|mysql|postgresql|mongodb|redis|docker|kubernetes|aws|azure|gcd|html|css|git)\b/gi);
                if (techSkills) {
                    for (const skill of techSkills) {
                        const cleanSkill = skill.trim();
                        if (cleanSkill && !result.skills.includes(cleanSkill)) {
                            result.skills.push(cleanSkill);
                            console.log('✅ Found skill in container:', cleanSkill);
                        }
                    }
                }
            }
        }
    }
    
    console.log('Final work type extracted:', result.workType);
    console.log('Final skills extracted:', result.skills);
    console.log('=== END WORK TYPE AND SKILLS EXTRACTION ===');
    
    return result;
};
