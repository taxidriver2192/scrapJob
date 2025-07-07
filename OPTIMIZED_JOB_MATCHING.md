# Optimized Job Matching System

## Quick Start

### 1. Create Your Configuration
```bash
make match-jobs-optimized-config
```
This creates `job_match_config.json` with default settings.

### 2. Customize Your Profile
Edit `job_match_config.json` to match your preferences:

```json
{
  "candidate": {
    "name": "Your Name/Title",
    "location": "Your City, Country", 
    "years_experience": 8,
    "primary_skills": ["PHP", "Laravel", "JavaScript", "AWS", "Docker", "Git"],
    "secondary_skills": ["Python", "Go", "IT Security", "MySQL", "Linux"],
    "preferred_roles": ["Senior Developer", "Lead Developer", "Full-Stack Developer"],
    "avoid_roles": ["Manager", "Director", "Head of", "VP"],
    "company_size": {
      "minimum": 50,
      "preferred": "50-500 employees"
    },
    "work_preferences": {
      "remote": true,
      "hybrid": true, 
      "on_site": true,
      "max_commute": "45 minutes from Your City"
    },
    "salary_range": {
      "minimum": 600000,
      "maximum": 800000
    },
    "languages": ["Danish", "English"]
  },
  "weights": {
    "location": 25,
    "tech_match": 35,
    "company_fit": 20,
    "seniority_fit": 15,
    "work_type_fit": 5
  }
}
```

### 3. Find Your Matches
```bash
# Test with a few jobs
make match-jobs-optimized-custom

# See detailed reasoning
make match-jobs-optimized-verbose

# Process all jobs (recommended after testing)
make match-jobs-optimized-all
```

## Configuration Guide

### Candidate Profile

#### Skills Configuration
- **primary_skills**: Your main technical expertise (heavily weighted)
- **secondary_skills**: Additional skills you have (moderately weighted)
- **Example for different roles**:
  ```json
  // Frontend Developer
  "primary_skills": ["JavaScript", "React", "TypeScript", "CSS", "HTML"],
  "secondary_skills": ["Node.js", "GraphQL", "Webpack", "Jest"]
  
  // DevOps Engineer
  "primary_skills": ["AWS", "Docker", "Kubernetes", "Terraform", "Python"],
  "secondary_skills": ["Jenkins", "Ansible", "Monitoring", "Linux"]
  
  // Data Scientist
  "primary_skills": ["Python", "Pandas", "Scikit-learn", "SQL", "R"],
  "secondary_skills": ["TensorFlow", "Docker", "Spark", "AWS"]
  ```

#### Role Preferences
- **preferred_roles**: Job titles you want to see
- **avoid_roles**: Job titles to score lower (management, different fields)
- **Examples**:
  ```json
  // Individual Contributor Focus
  "preferred_roles": ["Senior Developer", "Principal Engineer", "Staff Engineer"],
  "avoid_roles": ["Manager", "Director", "Team Lead", "Scrum Master"]
  
  // Leadership Track
  "preferred_roles": ["Tech Lead", "Engineering Manager", "Principal Engineer"],
  "avoid_roles": ["Junior", "Intern", "Director", "VP"]
  ```

#### Company Preferences
```json
"company_size": {
  "minimum": 50,           // Minimum employees
  "preferred": "50-500 employees"  // Description for AI
}
```

#### Work Preferences
```json
"work_preferences": {
  "remote": true,      // Open to fully remote
  "hybrid": true,      // Open to hybrid work
  "on_site": false,    // Not interested in full on-site
  "max_commute": "30 minutes from Copenhagen"
}
```

### Scoring Weights

Adjust these percentages based on your priorities (must total 100):

```json
"weights": {
  "location": 25,        // Geography and commute
  "tech_match": 35,      // Skills alignment
  "company_fit": 20,     // Company size/culture
  "seniority_fit": 15,   // Role level appropriateness  
  "work_type_fit": 5     // Remote/hybrid flexibility
}
```

#### Example Weight Configurations

**Remote-First Developer** (location less important):
```json
"weights": {
  "location": 10,
  "tech_match": 50,
  "company_fit": 20,
  "seniority_fit": 15,
  "work_type_fit": 5
}
```

**Senior Career Focus** (company and seniority more important):
```json
"weights": {
  "location": 20,
  "tech_match": 30,
  "company_fit": 25,
  "seniority_fit": 20,
  "work_type_fit": 5
}
```

**Flexible Commuter** (work arrangement very important):
```json
"weights": {
  "location": 30,
  "tech_match": 35,
  "company_fit": 15,
  "seniority_fit": 10,
  "work_type_fit": 10
}
```

## Commands Reference

### Basic Usage
```bash
# Create configuration
make match-jobs-optimized-config

# Use custom configuration (recommended)
make match-jobs-optimized-custom

# Detailed output
make match-jobs-optimized-verbose

# Process all jobs
make match-jobs-optimized-all

# Default settings (no config file)
make match-jobs-optimized
```

### Advanced Usage
```bash
# Custom parameters
go run cmd/match-jobs-optimized/main.go \
  --config job_match_config.json \
  --limit 50 \
  --min-score 70 \
  --verbose

# Multiple configurations
go run cmd/match-jobs-optimized/main.go --config frontend_profile.json
go run cmd/match-jobs-optimized/main.go --config backend_profile.json

# Dry run to see what would be processed
go run cmd/match-jobs-optimized/main.go --config job_match_config.json --dry-run
```

## Tips for Best Results

### 1. **Start Conservative**
- Use default weights initially
- Adjust gradually based on results
- Test with `--limit 10` before processing all jobs

### 2. **Skills Strategy**
- List 4-6 primary skills (your strongest areas)
- Include 4-8 secondary skills (additional competencies)
- Be specific: "Laravel" vs "PHP Framework"

### 3. **Score Interpretation**
- **90-100**: Excellent match, definitely apply
- **75-89**: Very good match, worth considering
- **60-74**: Good match, review carefully
- **50-59**: Possible match, check if interesting
- **Below 50**: Likely not a good fit

### 4. **Iterative Refinement**
```bash
# Try different minimum scores
--min-score 80   # Only excellent matches
--min-score 60   # Good and excellent matches
--min-score 40   # Broader view including possible matches
```

### 5. **Multiple Profiles**
Create different config files for different strategies:
- `current_role.json` - Jobs similar to current position
- `career_growth.json` - Next level up roles
- `career_pivot.json` - New technology/domain exploration

## Troubleshooting

### No Results Found
- Lower `--min-score` (try 40 or 30)
- Check if your skills are too specific
- Verify location preferences aren't too restrictive

### Too Many Results
- Increase `--min-score` (try 70 or 80)
- Make skills more specific
- Adjust weights to emphasize important factors

### Unexpected Scores
- Use `--verbose` to see AI reasoning
- Check if job data is incomplete
- Consider adjusting weights based on what matters most

## Examples

### Example 1: Senior PHP Developer
```bash
# Create config, edit skills to focus on PHP/Laravel
make match-jobs-optimized-config
# Edit: primary_skills: ["PHP", "Laravel", "MySQL", "JavaScript", "Git"]
make match-jobs-optimized-custom
```

### Example 2: DevOps Engineer Career Pivot
```json
{
  "candidate": {
    "primary_skills": ["AWS", "Docker", "Linux", "Python", "Terraform"],
    "secondary_skills": ["PHP", "JavaScript", "Git", "MySQL"],
    "preferred_roles": ["DevOps Engineer", "Cloud Engineer", "SRE", "Platform Engineer"],
    "avoid_roles": ["Developer", "PHP Developer", "Frontend Developer"]
  },
  "weights": {
    "tech_match": 45,     // Very important for career change
    "company_fit": 25,    // Need good learning environment
    "seniority_fit": 20,  // Appropriate level important
    "location": 10
  }
}
```

The optimized system gives you complete control over your job search preferences while maintaining the proven effectiveness of AI-powered job matching.
