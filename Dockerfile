FROM golang:1.21-alpine AS builder

WORKDIR /app

# Copy go mod files
COPY go.mod go.sum ./
RUN go mod download

# Copy source code
COPY . .

# Build the application
RUN CGO_ENABLED=0 GOOS=linux go build -o linkedin-scraper cmd/main.go

# Final stage
FROM chromium:latest

RUN apk add --no-cache ca-certificates

WORKDIR /root/

# Copy the binary from builder stage
COPY --from=builder /app/linkedin-scraper .

# Create directories
RUN mkdir -p logs chrome-profile

# Expose port (if you add a web server later)
EXPOSE 8080

CMD ["./linkedin-scraper"]
