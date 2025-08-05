package cache

import (
	"context"
	"encoding/json"
	"fmt"
	"linkedin-job-scraper/internal/config"
	"linkedin-job-scraper/internal/models"
	"time"

	"github.com/redis/go-redis/v9"
	"github.com/sirupsen/logrus"
)

type RedisCache struct {
	client       *redis.Client
	jobExistsTTL time.Duration
	cacheTTL     time.Duration
}

// NewRedisCache creates a new Redis cache instance
func NewRedisCache(cfg *config.RedisConfig) *RedisCache {
	client := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%s", cfg.Host, cfg.Port),
		Password: cfg.Password,
		DB:       cfg.DB,
	})

	// Test connection
	ctx := context.Background()
	if err := client.Ping(ctx).Err(); err != nil {
		logrus.Warnf("Redis connection failed: %v", err)
	} else {
		logrus.Info("✅ Redis cache connected successfully")
	}

	return &RedisCache{
		client:       client,
		jobExistsTTL: time.Duration(cfg.JobExistsTTL) * time.Second,
		cacheTTL:     time.Duration(cfg.CacheTTL) * time.Second,
	}
}

// JobExistsInCache checks if we recently verified that a job exists
func (r *RedisCache) JobExistsInCache(linkedinJobID int) (exists bool, found bool) {
	ctx := context.Background()
	key := fmt.Sprintf("job_exists:%d", linkedinJobID)
	
	result, err := r.client.Get(ctx, key).Result()
	if err == redis.Nil {
		// Key doesn't exist in cache
		return false, false
	}
	if err != nil {
		logrus.Warnf("Redis error checking job existence: %v", err)
		return false, false
	}
	
	// Key exists, check value
	exists = result == "true"
	return exists, true
}

// SetJobExists caches the fact that a job exists or doesn't exist
func (r *RedisCache) SetJobExists(linkedinJobID int, exists bool) {
	ctx := context.Background()
	key := fmt.Sprintf("job_exists:%d", linkedinJobID)
	value := "false"
	if exists {
		value = "true"
	}
	
	err := r.client.Set(ctx, key, value, r.jobExistsTTL).Err()
	if err != nil {
		logrus.Warnf("Redis error setting job existence: %v", err)
	} else {
		logrus.Debugf("🔄 Cached job existence: %d = %v (TTL: %v)", linkedinJobID, exists, r.jobExistsTTL)
	}
}

// GetCompanyByName gets a cached company by name
func (r *RedisCache) GetCompanyByName(name string) (*models.Company, bool) {
	ctx := context.Background()
	key := fmt.Sprintf("company:name:%s", name)
	
	result, err := r.client.Get(ctx, key).Result()
	if err == redis.Nil {
		return nil, false
	}
	if err != nil {
		logrus.Warnf("Redis error getting company: %v", err)
		return nil, false
	}
	
	var company models.Company
	if err := json.Unmarshal([]byte(result), &company); err != nil {
		logrus.Warnf("Redis error unmarshaling company: %v", err)
		return nil, false
	}
	
	logrus.Debugf("🎯 Company cache hit: %s", name)
	return &company, true
}

// SetCompany caches a company
func (r *RedisCache) SetCompany(company *models.Company) {
	ctx := context.Background()
	key := fmt.Sprintf("company:name:%s", company.Name)
	
	data, err := json.Marshal(company)
	if err != nil {
		logrus.Warnf("Redis error marshaling company: %v", err)
		return
	}
	
	err = r.client.Set(ctx, key, data, r.cacheTTL).Err()
	if err != nil {
		logrus.Warnf("Redis error setting company: %v", err)
	} else {
		logrus.Debugf("🔄 Cached company: %s (ID: %d)", company.Name, company.CompanyID)
	}
}

// InvalidateJobExists removes a job existence cache entry
func (r *RedisCache) InvalidateJobExists(linkedinJobID int) {
	ctx := context.Background()
	key := fmt.Sprintf("job_exists:%d", linkedinJobID)
	
	err := r.client.Del(ctx, key).Err()
	if err != nil {
		logrus.Warnf("Redis error deleting job existence cache: %v", err)
	}
}

// CompanyExistsInCache checks if we recently verified that a company exists
func (r *RedisCache) CompanyExistsInCache(companyName string) (exists bool, found bool) {
	ctx := context.Background()
	key := fmt.Sprintf("company_exists:%s", companyName)
	
	result, err := r.client.Get(ctx, key).Result()
	if err == redis.Nil {
		return false, false
	}
	if err != nil {
		logrus.Warnf("Redis error checking company existence: %v", err)
		return false, false
	}
	
	exists = result == "true"
	return exists, true
}

// SetCompanyExists caches the fact that a company exists or doesn't exist
func (r *RedisCache) SetCompanyExists(companyName string, exists bool) {
	ctx := context.Background()
	key := fmt.Sprintf("company_exists:%s", companyName)
	value := "false"
	if exists {
		value = "true"
	}
	
	err := r.client.Set(ctx, key, value, r.jobExistsTTL).Err()
	if err != nil {
		logrus.Warnf("Redis error setting company existence: %v", err)
	} else {
		logrus.Debugf("🔄 Cached company existence: %s = %v (TTL: %v)", companyName, exists, r.jobExistsTTL)
	}
}

// GetCacheStats returns some basic cache statistics
func (r *RedisCache) GetCacheStats() map[string]interface{} {
	ctx := context.Background()
	info, err := r.client.Info(ctx, "stats").Result()
	if err != nil {
		logrus.Warnf("Redis error getting stats: %v", err)
		return map[string]interface{}{"error": err.Error()}
	}
	
	return map[string]interface{}{
		"info": info,
		"connected": true,
	}
}

// Close closes the Redis connection
func (r *RedisCache) Close() error {
	return r.client.Close()
}
