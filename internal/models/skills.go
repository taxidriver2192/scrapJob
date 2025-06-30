package models

import (
	"database/sql/driver"
	"encoding/json"
	"fmt"
)

// SkillsList represents a list of skills stored as JSON
type SkillsList []string

// Value implements the driver.Valuer interface for database storage
func (sl SkillsList) Value() (driver.Value, error) {
	if sl == nil {
		return nil, nil
	}
	return json.Marshal(sl)
}

// Scan implements the sql.Scanner interface for database retrieval
func (sl *SkillsList) Scan(value interface{}) error {
	if value == nil {
		*sl = nil
		return nil
	}
	
	bytes, ok := value.([]byte)
	if !ok {
		return fmt.Errorf("cannot scan %T into SkillsList", value)
	}
	
	return json.Unmarshal(bytes, sl)
}
