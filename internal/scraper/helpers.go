package scraper

// minInt returns the minimum of two integers
func minInt(a, b int) int {
	if a < b {
		return a
	}
	return b
}

// getStringPointer safely extracts a string pointer from map data
func getStringPointer(data map[string]interface{}, key string) *string {
	if value, ok := data[key]; ok {
		if str, ok := value.(string); ok && str != "" {
			return &str
		}
	}
	return nil
}

// getIntPointer safely extracts an int pointer from map data
func getIntPointer(data map[string]interface{}, key string) *int {
	if value, ok := data[key]; ok {
		if intVal, ok := value.(int); ok {
			return &intVal
		}
		// Handle float64 from JSON
		if floatVal, ok := value.(float64); ok {
			intVal := int(floatVal)
			return &intVal
		}
	}
	return nil
}
