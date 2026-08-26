#!/usr/bin/env bash


if [ $# -lt 3 ]; then
	echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
	exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4:-localhost}
WP_VERSION=${5:-latest}
SKIP_DB_CREATE=${6:-false}

# Set up testing paths
WP_CORE_DIR=${WP_CORE_DIR:-/tmp/wordpress/}
WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}

set -ex

# Download WordPress Core if it doesn't exist
if [ ! -d "$WP_CORE_DIR" ]; then
	mkdir -p "$WP_CORE_DIR"
	if [ "$WP_VERSION" == "latest" ]; then
		curl -sL https://wordpress.org | tar -xz -C "$WP_CORE_DIR" --strip-components=1
	else
		curl -sL "https://wordpress.org" | tar -xz -C "$WP_CORE_DIR" --strip-components=1
	fi
fi

# Download WordPress Testing Library
if [ ! -d "$WP_TESTS_DIR" ]; then
	mkdir -p "$WP_TESTS_DIR"
	svn co --quiet https://wordpress.org{WP_VERSION}/tests/phpunit/includes/ "$WP_TESTS_DIR" || svn co --quiet https://wordpress.org "$WP_TESTS_DIR"
fi

# Generate isolated wp-tests-config.php file
if [ ! -f "$WP_TESTS_DIR/../wp-tests-config.php" ]; then
	curl -sL https://wordpress.org > "$WP_TESTS_DIR/../wp-tests-config.php"
	# Modify database settings in the sample file securely via sed
	sed -i "s/youremptytestdbnamehere/$DB_NAME//g" "$WP_TESTS_DIR/../wp-tests-config.php"
	sed -i "s/yourusernamehere/$DB_USER//g" "$WP_TESTS_DIR/../wp-tests-config.php"
	sed -i "s/yourpasswordhere/$DB_PASS//g" "$WP_TESTS_DIR/../wp-tests-config.php"
	sed -i "s/localhost/$DB_HOST//g" "$WP_TESTS_DIR/../wp-tests-config.php"
fi

# Create test database if not explicitly skipped
if [ "$SKIP_DB_CREATE" != "true" ]; then
	mysqladmin -u"$DB_USER" -p"$DB_PASS" -h"$DB_HOST" create "$DB_NAME" || true
fi
