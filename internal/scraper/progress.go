package scraper

import (
	"fmt"
	"strings"
	"sync"
	"time"
)

// ProgressTracker handles progress display and estimation
type ProgressTracker struct {
	total     int
	current   int
	startTime time.Time
	mutex     sync.RWMutex
	label     string
}

// NewProgressTracker creates a new progress tracker
func NewProgressTracker(total int, label string) *ProgressTracker {
	return &ProgressTracker{
		total:     total,
		current:   0,
		startTime: time.Now(),
		label:     label,
	}
}

// Update increments the progress and displays the progress bar
func (pt *ProgressTracker) Update() {
	pt.mutex.Lock()
	defer pt.mutex.Unlock()
	
	pt.current++
	pt.display()
}

// SetCurrent sets the current progress value
func (pt *ProgressTracker) SetCurrent(current int) {
	pt.mutex.Lock()
	defer pt.mutex.Unlock()
	
	pt.current = current
	pt.display()
}

// display shows the progress bar with time estimates
func (pt *ProgressTracker) display() {
	if pt.total == 0 {
		return
	}
	
	percentage := float64(pt.current) / float64(pt.total) * 100
	elapsed := time.Since(pt.startTime)
	
	// Calculate estimates
	var eta time.Duration
	var rate float64
	
	if pt.current > 0 {
		rate = float64(pt.current) / elapsed.Seconds() * 60 // jobs per minute
		remaining := pt.total - pt.current
		eta = time.Duration(float64(remaining)/rate*60) * time.Second
	}
	
	// Create progress bar
	barWidth := 40
	filled := int(float64(barWidth) * percentage / 100)
	bar := strings.Repeat("█", filled) + strings.Repeat("░", barWidth-filled)
	
	// Clear line and print progress
	fmt.Printf("\r%s [%s] %d/%d (%.1f%%) | %.1f/min | ETA: %s | Elapsed: %s",
		pt.label,
		bar,
		pt.current,
		pt.total,
		percentage,
		rate,
		formatDuration(eta),
		formatDuration(elapsed),
	)
	
	// If complete, add newline
	if pt.current >= pt.total {
		fmt.Println()
	}
}

// Finish completes the progress bar
func (pt *ProgressTracker) Finish() {
	pt.mutex.Lock()
	defer pt.mutex.Unlock()
	
	pt.current = pt.total
	pt.display()
}

// formatDuration formats a duration into a readable string
func formatDuration(d time.Duration) string {
	if d < time.Minute {
		return fmt.Sprintf("%ds", int(d.Seconds()))
	} else if d < time.Hour {
		return fmt.Sprintf("%dm%ds", int(d.Minutes()), int(d.Seconds())%60)
	}
	return fmt.Sprintf("%dh%dm", int(d.Hours()), int(d.Minutes())%60)
}

// OverallScrapingProgress tracks overall job scraping across multiple pages
type OverallScrapingProgress struct {
	targetJobs     int
	savedJobs      int
	skippedJobs    int
	failedJobs     int
	currentPage    int
	totalPages     int
	startTime      time.Time
	mutex          sync.RWMutex
}

// NewOverallScrapingProgress creates a new overall progress tracker
func NewOverallScrapingProgress(targetJobs int) *OverallScrapingProgress {
	return &OverallScrapingProgress{
		targetJobs:  targetJobs,
		savedJobs:   0,
		skippedJobs: 0,
		failedJobs:  0,
		currentPage: 0,
		totalPages:  0,
		startTime:   time.Now(),
	}
}

// UpdatePage updates the current page information
func (osp *OverallScrapingProgress) UpdatePage(page int, foundJobs, savedJobs, skippedJobs int) {
	osp.mutex.Lock()
	defer osp.mutex.Unlock()
	
	osp.currentPage = page
	osp.savedJobs += savedJobs
	osp.skippedJobs += skippedJobs
	osp.display(foundJobs, savedJobs, skippedJobs)
}

// AddFailed adds failed jobs to the count
func (osp *OverallScrapingProgress) AddFailed(count int) {
	osp.mutex.Lock()
	defer osp.mutex.Unlock()
	osp.failedJobs += count
}

// UpdateJob updates progress after processing individual jobs
func (osp *OverallScrapingProgress) UpdateJob(saved bool, skipped bool) {
	osp.mutex.Lock()
	defer osp.mutex.Unlock()
	
	if saved {
		osp.savedJobs++
	}
	if skipped {
		osp.skippedJobs++
	}
	
	// Update display without page-specific information
	osp.displaySimple()
}

// display shows the unified progress bar with comprehensive information
func (osp *OverallScrapingProgress) display(pageFound, pageSaved, pageSkipped int) {
	elapsed := time.Since(osp.startTime)
	
	// Calculate progress percentage based on saved jobs
	percentage := float64(osp.savedJobs) / float64(osp.targetJobs) * 100
	if percentage > 100 {
		percentage = 100
	}
	
	// Calculate rates
	var savedRate float64
	var eta time.Duration
	
	if osp.savedJobs > 0 {
		savedRate = float64(osp.savedJobs) / elapsed.Minutes()
		remaining := osp.targetJobs - osp.savedJobs
		if savedRate > 0 {
			eta = time.Duration(float64(remaining)/savedRate) * time.Minute
		}
	}
	
	// Create progress bar
	barWidth := 30
	filled := int(float64(barWidth) * percentage / 100)
	bar := strings.Repeat("█", filled) + strings.Repeat("░", barWidth-filled)
	
	// Show simplified progress in a single line that updates in place
	fmt.Printf("\r📊 [%s] %d/%d (%.1f%%) | Page %d | %.1f/min | Skipped: %d | ETA: %s",
		bar, osp.savedJobs, osp.targetJobs, percentage, osp.currentPage, 
		savedRate, osp.skippedJobs, formatDuration(eta))
}

// displaySimple shows a simplified progress update for individual job processing
func (osp *OverallScrapingProgress) displaySimple() {
	elapsed := time.Since(osp.startTime)
	
	// Calculate progress percentage based on saved jobs
	percentage := float64(osp.savedJobs) / float64(osp.targetJobs) * 100
	if percentage > 100 {
		percentage = 100
	}
	
	// Calculate rates
	var savedRate float64
	var eta time.Duration
	
	if osp.savedJobs > 0 {
		savedRate = float64(osp.savedJobs) / elapsed.Minutes()
		remaining := osp.targetJobs - osp.savedJobs
		if savedRate > 0 {
			eta = time.Duration(float64(remaining)/savedRate) * time.Minute
		}
	}
	
	// Create progress bar
	barWidth := 30
	filled := int(float64(barWidth) * percentage / 100)
	bar := strings.Repeat("█", filled) + strings.Repeat("░", barWidth-filled)
	
	// Show simplified progress
	fmt.Printf("\r📊 [%s] %d/%d (%.1f%%) | Page %d | %.1f/min | Skipped: %d | ETA: %s",
		bar, osp.savedJobs, osp.targetJobs, percentage, osp.currentPage, 
		savedRate, osp.skippedJobs, formatDuration(eta))
}

// Finish completes the progress display
func (osp *OverallScrapingProgress) Finish() {
	osp.mutex.Lock()
	defer osp.mutex.Unlock()
	
	elapsed := time.Since(osp.startTime)
	totalProcessed := osp.savedJobs + osp.skippedJobs + osp.failedJobs
	
	fmt.Printf("\n\n🎉 Scraping Complete!\n")
	fmt.Printf("   📈 Results: %d saved | %d skipped | %d failed (Total processed: %d)\n", 
		osp.savedJobs, osp.skippedJobs, osp.failedJobs, totalProcessed)
	fmt.Printf("   ⏱️  Time: %s | Rate: %.1f jobs/min | Success: %.1f%%\n", 
		formatDuration(elapsed), 
		float64(totalProcessed)/elapsed.Minutes(),
		float64(osp.savedJobs)/float64(totalProcessed)*100)
	fmt.Printf("   📄 Pages processed: %d\n", osp.currentPage)
}
