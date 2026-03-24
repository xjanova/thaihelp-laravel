#!/bin/bash

#########################################################
# ThaiHelp - Smart Automated Deployment Script
# For production and staging deployments
# Features:
#   - Smart migration handling (skip existing tables)
#   - Intelligent seeding (skip existing data)
#   - Detailed error logging and reporting
#   - Automatic rollback on failure
#########################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
BRANCH=${1:-main}
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="storage/backups"
LOG_DIR="storage/logs/deploy"
LOG_FILE="$LOG_DIR/deploy_${TIMESTAMP}.log"
ERROR_LOG="$LOG_DIR/error_${TIMESTAMP}.log"
DEPLOY_START=$(date +%s)

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Create log directories
mkdir -p "$LOG_DIR"
mkdir -p "$BACKUP_DIR"

# Logging functions
log() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] $1"
    echo "$message" >> "$LOG_FILE"
    echo -e "$2$1${NC}"
}

log_error() {
    local message="[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: $1"
    echo "$message" >> "$LOG_FILE"
    echo "$message" >> "$ERROR_LOG"
    echo -e "${RED}✗ $1${NC}"
}

log_error_detail() {
    local message="$1"
    echo "$message" >> "$ERROR_LOG"
    echo "$message" >> "$LOG_FILE"
}

# Functions
print_header() {
    echo -e "\n${CYAN}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║   🚀 ThaiHelp Smart Automated Deployment 🚀   ║${NC}"
    echo -e "${CYAN}║      Smart Migration & Seeding Support         ║${NC}"
    echo -e "${CYAN}╚════════════════════════════════════════════════╝${NC}\n"
    log "Deployment started" ""
}

print_step() {
    log "STEP: $1" "${BLUE}"
    echo -e "\n${BLUE}━━━ $1 ━━━${NC}"
}

print_success() {
    log "SUCCESS: $1" "${GREEN}"
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    log_error "$1"
}

print_warning() {
    log "WARNING: $1" "${YELLOW}"
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    log "INFO: $1" "${PURPLE}"
    echo -e "${PURPLE}ℹ $1${NC}"
}

# Generate error report
generate_error_report() {
    local step="$1"
    local error_message="$2"
    local error_output="$3"

    echo "" >> "$ERROR_LOG"
    echo "═══════════════════════════════════════════════════════════" >> "$ERROR_LOG"
    echo "ERROR REPORT - $(date '+%Y-%m-%d %H:%M:%S')" >> "$ERROR_LOG"
    echo "═══════════════════════════════════════════════════════════" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"
    echo "Step: $step" >> "$ERROR_LOG"
    echo "Branch: $BRANCH" >> "$ERROR_LOG"
    echo "Commit: $(git rev-parse --short HEAD 2>/dev/null || echo 'N/A')" >> "$ERROR_LOG"
    echo "Environment: $(grep "^APP_ENV=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo 'N/A')" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"
    echo "Error Message:" >> "$ERROR_LOG"
    echo "$error_message" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"
    echo "Error Output:" >> "$ERROR_LOG"
    echo "---" >> "$ERROR_LOG"
    echo "$error_output" >> "$ERROR_LOG"
    echo "---" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"

    # System info
    echo "System Information:" >> "$ERROR_LOG"
    echo "  PHP Version: $(php -v 2>/dev/null | head -1 || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Composer: $(composer --version 2>/dev/null | head -1 || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Node: $(node -v 2>/dev/null || echo 'N/A')" >> "$ERROR_LOG"
    echo "  NPM: $(npm -v 2>/dev/null || echo 'N/A')" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"

    # Database info
    echo "Database Information:" >> "$ERROR_LOG"
    echo "  Connection: $(grep "^DB_CONNECTION=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Host: $(grep "^DB_HOST=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Port: $(grep "^DB_PORT=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo 'N/A')" >> "$ERROR_LOG"
    echo "  Database: $(grep "^DB_DATABASE=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo 'N/A')" >> "$ERROR_LOG"
    echo "" >> "$ERROR_LOG"

    # Recent Laravel log
    if [ -f "storage/logs/laravel.log" ]; then
        echo "Recent Laravel Logs (last 50 lines):" >> "$ERROR_LOG"
        echo "---" >> "$ERROR_LOG"
        tail -50 storage/logs/laravel.log >> "$ERROR_LOG" 2>/dev/null || echo "Could not read Laravel log" >> "$ERROR_LOG"
        echo "---" >> "$ERROR_LOG"
    fi

    echo "" >> "$ERROR_LOG"
    echo "═══════════════════════════════════════════════════════════" >> "$ERROR_LOG"
}

# Sanitize .env file to fix common issues
sanitize_env_file() {
    print_step "Sanitizing Environment File"

    if [ ! -f .env ]; then
        print_warning ".env file not found, skipping sanitization"
        return 0
    fi

    # Create a backup
    cp .env .env.backup.${TIMESTAMP}
    print_info "Created backup: .env.backup.${TIMESTAMP}"

    # Fix common .env issues using awk
    awk '
    BEGIN { FS="="; OFS="=" }
    {
        # Skip empty lines and comments
        if ($0 ~ /^[[:space:]]*$/ || $0 ~ /^[[:space:]]*#/) {
            print $0
            next
        }

        # If line contains =, process it
        if (NF >= 2) {
            key = $1
            # Get everything after first =
            value = substr($0, length($1) + 2)

            # Remove trailing whitespace and newlines from value
            gsub(/[[:space:]]+$/, "", value)
            gsub(/\r/, "", value)
            gsub(/\n/, "", value)

            # Print cleaned line
            print key OFS value
        } else {
            # Print line as-is if it does not contain =
            print $0
        }
    }
    ' .env > .env.tmp && mv .env.tmp .env

    # Check for duplicate keys and keep only the first occurrence
    awk '
    BEGIN { FS="="; OFS="=" }
    !seen[$1]++ {
        print $0
    }
    ' .env > .env.tmp && mv .env.tmp .env

    print_success "Environment file sanitized"

    # Verify the file is valid
    set +e
    PHP_CHECK=$(php -r "
        if (file_exists('.env')) {
            \$lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            \$valid = true;
            foreach (\$lines as \$line) {
                \$line = trim(\$line);
                if (empty(\$line) || \$line[0] === '#') continue;
                if (strpos(\$line, '=') === false) {
                    echo 'invalid: Line without = found: ' . \$line;
                    \$valid = false;
                    break;
                }
            }
            if (\$valid) echo 'valid';
        } else {
            echo 'invalid: .env file not found';
        }
    " 2>&1)
    set -e

    if echo "$PHP_CHECK" | grep -q "invalid"; then
        print_warning "Environment file may have issues: $PHP_CHECK"
        print_info "Backup available at: .env.backup.${TIMESTAMP}"
    else
        print_success "Environment file validation passed"
    fi
}

# Check and generate APP_KEY if missing
check_app_key() {
    print_step "Checking Application Key"

    if [ ! -f .env ]; then
        print_warning ".env file not found, skipping APP_KEY check"
        return 0
    fi

    # Check if APP_KEY exists and is not empty
    APP_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2 | tr -d '[:space:]' || echo "")

    if [ -z "$APP_KEY" ]; then
        print_warning "APP_KEY is missing or empty"
        print_info "Generating new application key..."

        set +e
        KEYGEN_OUTPUT=$(php artisan key:generate --force 2>&1)
        KEYGEN_EXIT=$?
        set -e

        if [ $KEYGEN_EXIT -eq 0 ]; then
            print_success "Application key generated successfully"

            # Get the new key for display (masked)
            NEW_KEY=$(grep "^APP_KEY=" .env | cut -d'=' -f2 || echo "")
            if [ -n "$NEW_KEY" ]; then
                MASKED_KEY="${NEW_KEY:0:10}..."
                print_info "New key (masked): $MASKED_KEY"
            fi
        else
            print_error "Failed to generate application key"
            log_error_detail "Key generation output: $KEYGEN_OUTPUT"
            echo "$KEYGEN_OUTPUT"
            return 1
        fi
    else
        MASKED_KEY="${APP_KEY:0:10}..."
        print_success "Application key exists (masked): $MASKED_KEY"
    fi
}

# Check if in production
check_environment() {
    print_step "Checking Environment"

    if [ ! -f .env ]; then
        print_error ".env file not found"
        print_info "Please create .env from .env.example first"
        exit 1
    fi

    if grep -q "^APP_ENV=production" .env; then
        print_warning "Deploying to PRODUCTION environment"
        print_info "Continuing deployment automatically..."
    else
        APP_ENV=$(grep "^APP_ENV=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "unknown")
        print_info "Deploying to $APP_ENV environment"
    fi

    print_success "Environment check passed"
}

# Create database backup before migration (MySQL only)
backup_database() {
    print_step "Backing Up Database"

    # Get database type
    DB_CONNECTION=$(grep "^DB_CONNECTION=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "")

    if [ "$DB_CONNECTION" = "mysql" ] || [ "$DB_CONNECTION" = "mariadb" ]; then
        DB_HOST=$(grep "^DB_HOST=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "127.0.0.1")
        DB_PORT=$(grep "^DB_PORT=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "3306")
        DB_DATABASE=$(grep "^DB_DATABASE=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "")
        DB_USERNAME=$(grep "^DB_USERNAME=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "")
        DB_PASSWORD=$(grep "^DB_PASSWORD=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "")

        # Validate that required database credentials exist
        if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
            print_warning "Database credentials incomplete, skipping backup"
            print_info "DB_DATABASE='$DB_DATABASE', DB_USERNAME='$DB_USERNAME'"
            return 0
        fi

        BACKUP_FILE="$BACKUP_DIR/backup_${TIMESTAMP}.sql"

        if command -v mysqldump >/dev/null 2>&1; then
            print_info "Creating MySQL backup..."
            print_info "Using DB_HOST=$DB_HOST, DB_PORT=$DB_PORT, DB_DATABASE=$DB_DATABASE"

            set +e
            BACKUP_OUTPUT=$(mysqldump -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" 2>&1)
            BACKUP_EXIT=$?
            set -e

            if [ $BACKUP_EXIT -eq 0 ]; then
                echo "$BACKUP_OUTPUT" > "$BACKUP_FILE"
                BACKUP_SIZE=$(du -h "$BACKUP_FILE" 2>/dev/null | cut -f1 || echo "unknown")
                print_success "Database backed up to $BACKUP_FILE ($BACKUP_SIZE)"
            else
                print_warning "Could not create backup: $BACKUP_OUTPUT"
                log_error_detail "Backup failed: $BACKUP_OUTPUT"
                log_error_detail "DB_HOST='$DB_HOST', DB_PORT='$DB_PORT', DB_DATABASE='$DB_DATABASE'"
            fi
        else
            print_warning "mysqldump not available, skipping backup"
        fi
    else
        print_warning "Database type is '$DB_CONNECTION' (not MySQL), skipping backup"
    fi
}

# Cleanup old backup files (older than 2 days)
cleanup_old_backups() {
    print_step "Cleaning Up Old Backups"

    local DELETED_COUNT=0

    # Clean .env backup files older than 2 days
    if ls .env.backup.* >/dev/null 2>&1; then
        print_info "Checking .env backup files..."
        while IFS= read -r file; do
            if [ -f "$file" ]; then
                rm -f "$file"
                print_info "Deleted: $file"
                DELETED_COUNT=$((DELETED_COUNT + 1))
            fi
        done < <(find . -maxdepth 1 -name ".env.backup.*" -type f -mtime +2)
    fi

    # Clean database backup files older than 2 days
    if [ -d "$BACKUP_DIR" ]; then
        print_info "Checking database backup files in $BACKUP_DIR..."
        while IFS= read -r file; do
            if [ -f "$file" ]; then
                rm -f "$file"
                print_info "Deleted: $file"
                DELETED_COUNT=$((DELETED_COUNT + 1))
            fi
        done < <(find "$BACKUP_DIR" -type f -name "backup_*.sql" -mtime +2)
    fi

    # Clean old deploy log files older than 2 days
    if [ -d "$LOG_DIR" ]; then
        print_info "Checking old deploy logs in $LOG_DIR..."
        while IFS= read -r file; do
            if [ -f "$file" ]; then
                rm -f "$file"
                print_info "Deleted: $file"
                DELETED_COUNT=$((DELETED_COUNT + 1))
            fi
        done < <(find "$LOG_DIR" -type f \( -name "deploy_*.log" -o -name "error_*.log" \) -mtime +2)
    fi

    if [ $DELETED_COUNT -eq 0 ]; then
        print_success "No old backup files to clean (keeping files newer than 2 days)"
    else
        print_success "Deleted $DELETED_COUNT old file(s)"
    fi
}

# Enable maintenance mode
enable_maintenance() {
    print_step "Enabling Maintenance Mode"

    php artisan down --retry=60 2>&1 || true
    print_success "Application is now in maintenance mode"
}

# Disable maintenance mode
disable_maintenance() {
    print_step "Disabling Maintenance Mode"

    php artisan up 2>&1
    print_success "Application is now live"
}

# Pull latest code
pull_code() {
    print_step "Pulling Latest Code"

    if [ -d .git ]; then
        print_info "Fetching from repository..."

        set +e
        GIT_OUTPUT=$(git fetch origin 2>&1)
        GIT_EXIT=$?
        set -e

        if [ $GIT_EXIT -ne 0 ]; then
            print_error "Git fetch failed"
            generate_error_report "pull_code" "Git fetch failed" "$GIT_OUTPUT"
            return 1
        fi

        # Reset local changes to avoid conflicts
        git checkout -- . 2>/dev/null || true
        git reset --hard HEAD 2>/dev/null || true
        git clean -fd 2>/dev/null || true

        print_info "Pulling branch: $BRANCH"

        set +e
        GIT_OUTPUT=$(git pull origin "$BRANCH" 2>&1)
        GIT_EXIT=$?
        set -e

        if [ $GIT_EXIT -ne 0 ]; then
            print_error "Git pull failed"
            generate_error_report "pull_code" "Git pull failed for branch $BRANCH" "$GIT_OUTPUT"
            return 1
        fi

        CURRENT_COMMIT=$(git rev-parse --short HEAD)
        print_success "Updated to commit: $CURRENT_COMMIT"
    else
        print_warning "Not a git repository, skipping code pull"
    fi
}

# Install/Update dependencies
update_dependencies() {
    print_step "Updating Dependencies"

    # Composer
    print_info "Updating PHP dependencies..."

    set +e
    COMPOSER_OUTPUT=$(composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev 2>&1)
    COMPOSER_EXIT=$?
    set -e

    if [ $COMPOSER_EXIT -ne 0 ]; then
        print_error "Composer install failed"
        generate_error_report "update_dependencies" "Composer install failed" "$COMPOSER_OUTPUT"
        echo "$COMPOSER_OUTPUT"
        return 1
    fi

    print_success "Composer dependencies updated"

    # NPM
    if command -v npm >/dev/null 2>&1; then
        print_info "Updating Node.js dependencies..."

        set +e
        if [ -f package-lock.json ]; then
            NPM_OUTPUT=$(npm ci 2>&1)
        else
            NPM_OUTPUT=$(npm install 2>&1)
        fi
        NPM_EXIT=$?
        set -e

        if [ $NPM_EXIT -ne 0 ]; then
            print_warning "NPM install had issues (non-fatal)"
            log_error_detail "NPM install output: $NPM_OUTPUT"
        else
            print_success "NPM dependencies updated"
        fi
    else
        print_warning "NPM not available, skipping Node.js dependencies"
    fi
}

# Build frontend assets
build_assets() {
    print_step "Building Frontend Assets"

    if command -v npm >/dev/null 2>&1; then
        print_info "Building production assets..."

        set +e
        BUILD_OUTPUT=$(npm run build 2>&1)
        BUILD_EXIT=$?
        set -e

        if [ $BUILD_EXIT -ne 0 ]; then
            print_error "Asset build failed"
            generate_error_report "build_assets" "npm run build failed" "$BUILD_OUTPUT"
            echo "$BUILD_OUTPUT"
            return 1
        fi

        print_success "Assets built successfully"

        # Convert images to WebP
        echo ">>> Converting images to WebP..."
        php artisan images:convert-webp --quality=80 2>&1 || true
    else
        print_warning "NPM not available, skipping asset build"
    fi
}

# Smart database migrations
run_migrations() {
    print_step "Running Smart Database Migrations"

    # Clear config cache to ensure fresh database config
    php artisan config:clear 2>/dev/null || true

    # Check if there are pending migrations
    print_info "Checking for pending migrations..."

    set +e
    MIGRATION_STATUS=$(php artisan migrate:status 2>&1)
    MIGRATION_STATUS_EXIT=$?
    PENDING_COUNT=$(echo "$MIGRATION_STATUS" | grep -c "Pending" || echo "0")
    set -e

    if [ $MIGRATION_STATUS_EXIT -ne 0 ]; then
        # Check if the error is due to missing migrations table
        if echo "$MIGRATION_STATUS" | grep -q "Migration table not found\|Base table or view not found.*migrations"; then
            print_warning "Migration table not found - this appears to be a fresh database"
            print_info "Installing migrations table..."

            set +e
            INSTALL_OUTPUT=$(php artisan migrate:install 2>&1)
            INSTALL_EXIT=$?
            set -e

            if [ $INSTALL_EXIT -ne 0 ]; then
                print_error "Failed to install migrations table"
                generate_error_report "run_migrations" "migrate:install failed" "$INSTALL_OUTPUT"
                echo "$INSTALL_OUTPUT"
                return 1
            fi

            print_success "Migrations table created"

            # Retry getting migration status
            set +e
            MIGRATION_STATUS=$(php artisan migrate:status 2>&1)
            MIGRATION_STATUS_EXIT=$?
            PENDING_COUNT=$(echo "$MIGRATION_STATUS" | grep -c "Pending" || echo "0")
            set -e

            if [ $MIGRATION_STATUS_EXIT -ne 0 ]; then
                print_error "Could not check migration status after installing table"
                generate_error_report "run_migrations" "migrate:status failed after install" "$MIGRATION_STATUS"
                echo "$MIGRATION_STATUS"
                return 1
            fi
        else
            print_error "Could not check migration status"
            generate_error_report "run_migrations" "migrate:status failed" "$MIGRATION_STATUS"
            echo "$MIGRATION_STATUS"
            return 1
        fi
    fi

    if [ "$PENDING_COUNT" = "0" ]; then
        print_success "No pending migrations"
        return 0
    fi

    print_warning "Found $PENDING_COUNT pending migration(s)"

    # Run migrations with error handling
    set +e
    MIGRATION_OUTPUT=$(php artisan migrate --force 2>&1)
    MIGRATION_EXIT=$?
    set -e

    echo "$MIGRATION_OUTPUT"
    log_error_detail "Migration output: $MIGRATION_OUTPUT"

    if [ $MIGRATION_EXIT -eq 0 ]; then
        print_success "All migrations completed successfully"
        return 0
    fi

    # Handle specific errors - table already exists
    if echo "$MIGRATION_OUTPUT" | grep -q "already exists"; then
        print_warning "Some tables already exist, attempting to analyze..."

        FAILED_TABLE=$(echo "$MIGRATION_OUTPUT" | grep -oP "Table '\K[^']+" | head -1)
        FAILED_MIGRATION_FILE=$(echo "$MIGRATION_OUTPUT" | grep -oP "\d{4}_\d{2}_\d{2}_\d+_\w+" | head -1)

        print_info "Table '$FAILED_TABLE' already exists"
        print_info "Migration file: $FAILED_MIGRATION_FILE"

        generate_error_report "run_migrations" "Table already exists: $FAILED_TABLE" "$MIGRATION_OUTPUT"

        print_error "Migration failed. Please check error log: $ERROR_LOG"
        echo ""
        echo "  Suggested fixes:"
        echo "  1. Check if migration file has Schema::hasTable() check"
        echo "  2. Run: php artisan migrate:status"
        echo "  3. If safe, run: php artisan migrate:fresh --force (DELETES ALL DATA!)"
        echo ""

        # Rollback the failed migration
        print_warning "Attempting rollback of failed migration..."
        set +e
        ROLLBACK_OUTPUT=$(php artisan migrate:rollback --force 2>&1)
        ROLLBACK_EXIT=$?
        set -e

        if [ $ROLLBACK_EXIT -eq 0 ]; then
            print_info "Rollback completed: $ROLLBACK_OUTPUT"
        else
            print_warning "Rollback also failed: $ROLLBACK_OUTPUT"
        fi

        return 1
    fi

    # Unknown migration error - attempt rollback
    print_error "Migration failed with unknown error"
    generate_error_report "run_migrations" "Migration failed with unknown error" "$MIGRATION_OUTPUT"

    print_warning "Attempting rollback..."
    set +e
    ROLLBACK_OUTPUT=$(php artisan migrate:rollback --force 2>&1)
    ROLLBACK_EXIT=$?
    set -e

    if [ $ROLLBACK_EXIT -eq 0 ]; then
        print_info "Rollback completed: $ROLLBACK_OUTPUT"
    else
        print_warning "Rollback also failed: $ROLLBACK_OUTPUT"
    fi

    print_info "Check error log: $ERROR_LOG"
    return 1
}

# Smart seeding
run_seeders() {
    print_step "Running Smart Database Seeding"

    # Check if seeders exist
    if [ ! -d "database/seeders" ]; then
        print_info "No seeders directory found, skipping"
        return 0
    fi

    # Run the smart seeder if it exists
    if [ -f "database/seeders/SmartDatabaseSeeder.php" ]; then
        print_info "Running SmartDatabaseSeeder..."

        set +e
        SEED_OUTPUT=$(php artisan db:seed --class=SmartDatabaseSeeder --force 2>&1)
        SEED_EXIT=$?
        set -e

        echo "$SEED_OUTPUT"

        if [ $SEED_EXIT -ne 0 ]; then
            if echo "$SEED_OUTPUT" | grep -q "Unknown column"; then
                UNKNOWN_COL=$(echo "$SEED_OUTPUT" | grep -oP "Unknown column '\K[^']+" | head -1)
                AFFECTED_TABLE=$(echo "$SEED_OUTPUT" | grep -oP "INSERT INTO \`\K[^\`]+" | head -1)

                print_warning "Column mismatch detected!"
                print_info "Column '$UNKNOWN_COL' does not exist in table '$AFFECTED_TABLE'"
                generate_error_report "run_seeders" "Column mismatch: $UNKNOWN_COL in $AFFECTED_TABLE" "$SEED_OUTPUT"

                echo ""
                echo "  Suggested fixes:"
                echo "  1. Check SmartDatabaseSeeder uses correct column names"
                echo "  2. Run: php artisan migrate:status"
                echo "  3. Compare seeder data with migration schema"
                echo ""

                # Check if individual seeding methods have try-catch
                if echo "$SEED_OUTPUT" | grep -q "✗ Failed to add"; then
                    print_info "Some items were skipped due to errors (see above)"
                    print_warning "Seeding completed with partial errors"
                    return 0
                fi

                return 1
            elif echo "$SEED_OUTPUT" | grep -q "Table .* doesn't exist"; then
                MISSING_TABLE=$(echo "$SEED_OUTPUT" | grep -oP "Table '.*?\.\K[^']+" | head -1)
                print_warning "Table '$MISSING_TABLE' does not exist"
                generate_error_report "run_seeders" "Missing table: $MISSING_TABLE" "$SEED_OUTPUT"
                return 1
            else
                print_error "Smart seeding failed with unknown error"
                generate_error_report "run_seeders" "SmartDatabaseSeeder failed" "$SEED_OUTPUT"
                return 1
            fi
        fi

        # Check for partial failures
        if echo "$SEED_OUTPUT" | grep -q "✗ Failed"; then
            print_warning "Seeding completed with some partial failures (non-fatal)"
            print_info "Check output above for details"
        else
            print_success "Smart seeding completed successfully"
        fi

    elif [ -f "database/seeders/DatabaseSeeder.php" ]; then
        # Check if we should run seeders (only if tables are empty)
        print_info "Checking if seeding is needed..."

        set +e
        SHOULD_SEED=$(php artisan tinker --execute="echo \App\Models\User::count() == 0 ? 'yes' : 'no';" 2>/dev/null | tail -1)
        set -e

        if [ "$SHOULD_SEED" = "yes" ]; then
            print_info "Running DatabaseSeeder..."

            set +e
            SEED_OUTPUT=$(php artisan db:seed --force 2>&1)
            SEED_EXIT=$?
            set -e

            if [ $SEED_EXIT -ne 0 ]; then
                print_warning "Seeding had issues (non-fatal)"
                log_error_detail "Seeding output: $SEED_OUTPUT"
            else
                print_success "Seeding completed"
            fi
        else
            print_info "Users exist, skipping initial seeding"
        fi

    else
        print_info "No seeders found, skipping"
    fi
}

# Clear and rebuild caches
optimize_application() {
    print_step "Clearing and Rebuilding Caches"

    # IMPORTANT: Optimize autoloader FIRST (before config:cache)
    # config:cache serializes class references — autoloader must be ready!
    print_info "Optimizing autoloader..."
    composer dump-autoload --optimize 2>&1
    print_success "Autoloader optimized"

    # Clear all caches first
    print_info "Clearing caches..."
    php artisan config:clear 2>&1
    php artisan route:clear 2>&1
    php artisan view:clear 2>&1
    php artisan event:clear 2>&1
    php artisan cache:clear 2>&1 || true
    print_success "Caches cleared"

    # Rebuild caches for production
    print_info "Rebuilding caches for production..."

    set +e
    php artisan config:cache 2>&1 || print_warning "config:cache had issues"
    # NOTE: route:cache skipped — api.php uses Closure routes (incompatible with route caching)
    # php artisan route:cache 2>&1 || print_warning "route:cache had issues"
    php artisan view:cache 2>&1 || print_warning "view:cache had issues"
    php artisan event:cache 2>&1 || print_warning "event:cache had issues"
    php artisan filament:cache-components 2>&1 || true
    php artisan icons:cache 2>&1 || true
    set -e

    print_success "Application optimized for production"
}

# Fix permissions
fix_permissions() {
    print_step "Fixing File Permissions"

    chmod -R 775 storage bootstrap/cache 2>&1
    print_success "Permissions fixed (storage, bootstrap/cache - 775)"
}

# Handle deployment failure
on_error() {
    local exit_code=$?
    print_error "Deployment failed! (Exit code: $exit_code)"

    # Try to bring the app back up
    php artisan up 2>/dev/null || true

    echo ""
    echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}                    DEPLOYMENT FAILED                        ${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "${YELLOW}Error logs saved to:${NC}"
    echo -e "  ${PURPLE}Full log:${NC}  $LOG_FILE"
    echo -e "  ${PURPLE}Error log:${NC} $ERROR_LOG"
    echo ""
    echo -e "${YELLOW}To view error details:${NC}"
    echo -e "  cat $ERROR_LOG"
    echo ""
    echo -e "${YELLOW}To retry deployment:${NC}"
    echo -e "  ./deploy.sh $BRANCH"
    echo ""

    exit 1
}

# Print deployment summary
print_summary() {
    local DEPLOY_END=$(date +%s)
    local DEPLOY_DURATION=$((DEPLOY_END - DEPLOY_START))
    local DEPLOY_MINUTES=$((DEPLOY_DURATION / 60))
    local DEPLOY_SECONDS=$((DEPLOY_DURATION % 60))

    echo -e "\n${GREEN}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✓ Deployment Completed Successfully!        ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════╝${NC}\n"

    print_info "Deployment finished at $(date)"
    print_success "ThaiHelp is now live!"

    echo -e "\n${CYAN}Deployment Summary:${NC}"
    echo -e "  ${PURPLE}Branch:${NC}      $BRANCH"
    if [ -d .git ]; then
        echo -e "  ${PURPLE}Commit:${NC}      $(git rev-parse --short HEAD)"
        echo -e "  ${PURPLE}Message:${NC}     $(git log -1 --pretty=%s 2>/dev/null || echo 'N/A')"
    fi
    APP_ENV=$(grep "^APP_ENV=" .env 2>/dev/null | head -1 | cut -d'=' -f2 | tr -d '\r\n' | xargs || echo "unknown")
    echo -e "  ${PURPLE}Environment:${NC} $APP_ENV"
    echo -e "  ${PURPLE}Duration:${NC}    ${DEPLOY_MINUTES}m ${DEPLOY_SECONDS}s"
    echo -e "  ${PURPLE}Log:${NC}         $LOG_FILE"
    echo
}

# ═══════════════════════════════════════════════════════════
# Main deployment flow
# ═══════════════════════════════════════════════════════════
main() {
    print_header

    print_info "Starting deployment at $(date)"
    print_info "Branch: $BRANCH"
    print_info "Log file: $LOG_FILE"
    echo

    # Set trap for errors
    trap on_error ERR

    # Step 1: Environment preparation
    sanitize_env_file
    check_app_key
    check_environment

    # Step 2: Database backup
    backup_database
    cleanup_old_backups

    # Step 3: Maintenance mode
    enable_maintenance

    # Step 4: Pull code
    pull_code

    # Step 5: Dependencies
    update_dependencies

    # Step 6: Build assets
    build_assets

    # Step 7: Database
    run_migrations
    run_seeders

    # Step 8: Optimize
    optimize_application

    # Step 9: Permissions
    fix_permissions

    # Step 10: Go live
    disable_maintenance

    # Done
    print_summary
}

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --branch=*)
            BRANCH="${1#*=}"
            shift
            ;;
        --no-backup)
            SKIP_BACKUP=1
            shift
            ;;
        --verbose|-v)
            VERBOSE=1
            shift
            ;;
        --help|-h)
            echo "ThaiHelp - Smart Automated Deployment Script"
            echo ""
            echo "Usage: ./deploy.sh [branch] [options]"
            echo ""
            echo "Arguments:"
            echo "  branch              Git branch to deploy (default: main)"
            echo ""
            echo "Options:"
            echo "  --branch=NAME       Specify branch name"
            echo "  --no-backup         Skip database backup"
            echo "  --verbose, -v       Enable verbose output"
            echo "  --help, -h          Show this help message"
            echo ""
            echo "Examples:"
            echo "  ./deploy.sh                  Deploy main branch"
            echo "  ./deploy.sh develop          Deploy develop branch"
            echo "  ./deploy.sh --branch=staging Deploy staging branch"
            echo ""
            exit 0
            ;;
        *)
            if [ -z "${BRANCH_SET:-}" ]; then
                BRANCH="$1"
                BRANCH_SET=1
            fi
            shift
            ;;
    esac
done

# Run main deployment
main
