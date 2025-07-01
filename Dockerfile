# Web Dashboard Dockerfile (minimal, no scraping dependencies)
FROM golang:1.21-alpine AS builder

WORKDIR /app

# Copy go mod files
COPY go.mod go.sum ./
RUN go mod download

# Copy source code
COPY . .

# Build only the web dashboard
RUN CGO_ENABLED=0 GOOS=linux go build -o web-dashboard cmd/web-dashboard/main.go

# Final runtime stage
FROM alpine:3.18

# Install minimal dependencies
RUN apk add --no-cache \
    ca-certificates \
    wget \
    tzdata \
    && addgroup -g 1001 -S linkedin \
    && adduser -S linkedin -u 1001

WORKDIR /app

# Copy only the web dashboard binary
COPY --from=builder /app/web-dashboard .

# Create directories and set ownership
RUN mkdir -p logs backups \
    && chown -R linkedin:linkedin /app

# Switch to non-root user
USER linkedin

# Expose port for web dashboard
EXPOSE 8081

# Start web dashboard
CMD ["./web-dashboard"]
