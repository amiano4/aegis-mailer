# PHPMail Service - Development Makefile

.PHONY: help install test test-unit test-integration test-feature test-coverage
.PHONY: cs cs-fix stan quality serve clean
.PHONY: docker-build docker-test docker-clean mailhog-start mailhog-stop
.DEFAULT_GOAL := help

# Colors for output
BLUE := \033[36m
GREEN := \033[32m
YELLOW := \033[33m
RED := \033[31m
NC := \033[0m

help: ## Show this help message
	@echo "${BLUE}PHPMail Service - Development Commands${NC}"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "${GREEN}%-20s${NC} %s\n", $$1, $$2}'

install: ## Install dependencies
	@echo "${BLUE}Installing dependencies...${NC}"
	composer install

install-prod: ## Install production dependencies only
	@echo "${BLUE}Installing production dependencies...${NC}"
	composer install --no-dev --optimize-autoloader

update: ## Update dependencies
	@echo "${BLUE}Updating dependencies...${NC}"
	composer update

# Testing
test: ## Run all tests
	@echo "${BLUE}Running all tests...${NC}"
	vendor/bin/phpunit

test-unit: ## Run unit tests only
	@echo "${BLUE}Running unit tests...${NC}"
	vendor/bin/phpunit --testsuite="Unit Tests" --no-coverage

test-integration: ## Run integration tests only
	@echo "${BLUE}Running integration tests...${NC}"
	vendor/bin/phpunit --testsuite="Integration Tests" --no-coverage

test-feature: ## Run feature tests only
	@echo "${BLUE}Running feature tests...${NC}"
	vendor/bin/phpunit --testsuite="Feature Tests" --no-coverage

test-coverage: ## Generate test coverage report
	@echo "${BLUE}Generating coverage report...${NC}"
	vendor/bin/phpunit --coverage-html coverage
	@echo "${GREEN}Coverage report generated in ./coverage/index.html${NC}"

# Code Quality
cs: ## Check code style
	@echo "${BLUE}Checking code style...${NC}"
	vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Fix code style issues
	@echo "${BLUE}Fixing code style...${NC}"
	vendor/bin/php-cs-fixer fix

stan: ## Run static analysis
	@echo "${BLUE}Running static analysis...${NC}"
	vendor/bin/phpstan analyse

quality: ## Run all quality checks
	@echo "${BLUE}Running all quality checks...${NC}"
	@make cs
	@make stan
	@make test-unit

# Development Server
serve: ## Start development server
	@echo "${BLUE}Starting development server on http://localhost:8000${NC}"
	php -S localhost:8000 public/index.php

serve-bg: ## Start development server in background
	@echo "${BLUE}Starting development server in background...${NC}"
	nohup php -S localhost:8000 public/index.php > /dev/null 2>&1 &
	@echo "${GREEN}Server started at http://localhost:8000${NC}"

stop-serve: ## Stop background development server
	@echo "${BLUE}Stopping development server...${NC}"
	pkill -f "php -S localhost:8000"

# MailHog (for testing)
mailhog-start: ## Start MailHog for email testing
	@echo "${BLUE}Starting MailHog...${NC}"
	@if command -v mailhog >/dev/null 2>&1; then \
		nohup mailhog > /dev/null 2>&1 & \
		echo "${GREEN}MailHog started at http://localhost:8025${NC}"; \
	else \
		echo "${YELLOW}MailHog not installed. Install with: go get github.com/mailhog/MailHog${NC}"; \
	fi

mailhog-stop: ## Stop MailHog
	@echo "${BLUE}Stopping MailHog...${NC}"
	pkill -f mailhog || true

# Docker
docker-build: ## Build Docker image
	@echo "${BLUE}Building Docker image...${NC}"
	docker build -t phpmail-service .

docker-test: ## Run tests in Docker
	@echo "${BLUE}Running tests in Docker...${NC}"
	docker run --rm -v $(PWD):/app -w /app phpmail-service make test

docker-clean: ## Clean Docker images
	@echo "${BLUE}Cleaning Docker images...${NC}"
	docker rmi phpmail-service || true

# Utilities
clean: ## Clean temporary files and caches
	@echo "${BLUE}Cleaning temporary files...${NC}"
	rm -rf vendor/
	rm -rf coverage/
	rm -rf logs/*.log
	rm -f .phpunit.result.cache
	rm -f composer.lock

logs: ## Show recent logs
	@echo "${BLUE}Recent email service logs:${NC}"
	@if [ -f logs/email-$(shell date +%Y-%m-%d).log ]; then \
		tail -20 logs/email-$(shell date +%Y-%m-%d).log; \
	else \
		echo "${YELLOW}No logs found for today${NC}"; \
	fi

health: ## Check service health
	@echo "${BLUE}Checking service health...${NC}"
	@if curl -s http://localhost:8000/health > /dev/null; then \
		curl -s http://localhost:8000/health | jq .; \
	else \
		echo "${RED}Service not running or not healthy${NC}"; \
	fi

status: ## Check service status
	@echo "${BLUE}Checking service status...${NC}"
	@if curl -s http://localhost:8000/status > /dev/null; then \
		curl -s http://localhost:8000/status | jq .; \
	else \
		echo "${RED}Service not running${NC}"; \
	fi

# Test with real email
test-email: ## Send a test email (requires running service)
	@echo "${BLUE}Sending test email...${NC}"
	@curl -X POST http://localhost:8000 \
		-H "Content-Type: application/json" \
		-d '{"to":"test@example.com","subject":"Test from Makefile","body":"This is a test email sent via Makefile"}' \
		| jq .

# Development workflow
dev: install mailhog-start serve-bg ## Setup development environment
	@echo "${GREEN}Development environment ready!${NC}"
	@echo "Email service: ${BLUE}http://localhost:8000${NC}"
	@echo "MailHog UI: ${BLUE}http://localhost:8025${NC}"

dev-stop: stop-serve mailhog-stop ## Stop development environment
	@echo "${GREEN}Development environment stopped${NC}"

# CI/CD simulation
ci: install quality test ## Simulate CI/CD pipeline
	@echo "${GREEN}CI/CD pipeline completed successfully!${NC}"