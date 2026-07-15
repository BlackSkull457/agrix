#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DIST_DIR="$ROOT_DIR/build"
ZIP_FILE="$DIST_DIR/agrix-site.zip"

function print_help() {
    cat <<EOF
Usage: ./build.sh [command]

Commands:
  check     Validate PHP syntax for all PHP files in the repository.
  package   Create a distribution ZIP archive under build/agrix-site.zip.
  all       Run both check and package (default).
  help      Show this help message.
EOF
}

function require_php() {
    if ! command -v php >/dev/null 2>&1; then
        echo "ERROR: PHP is required but not installed or not in PATH." >&2
        exit 1
    fi
}

function run_check() {
    require_php
    echo "Checking PHP syntax..."
    local errors=0
    while IFS= read -r -d '' file; do
        if ! php -l "$file" >/dev/null 2>&1; then
            echo "Syntax error in: $file"
            php -l "$file"
            errors=$((errors + 1))
        fi
    done < <(find "$ROOT_DIR" -type f -name '*.php' -print0)

    if [[ $errors -gt 0 ]]; then
        echo "\n$errors PHP file(s) failed syntax check." >&2
        exit 1
    fi
    echo "PHP syntax check passed."
}

function run_package() {
    echo "Creating distribution package..."
    rm -rf "$DIST_DIR"
    mkdir -p "$DIST_DIR"

    pushd "$ROOT_DIR" >/dev/null
    zip -r "$ZIP_FILE" \
        check_users.php dashboard.php informasi_pasar.php login.php logout.php mark_notifications.php \
        market_sync.php notifikasi.php rekomendasi_harga.php test.php update_stok.php \
        admin api assets config Gambar main produk uploads \
        .htaccess 2>/dev/null || true
    popd >/dev/null

    echo "Package created at: $ZIP_FILE"
}

COMMAND="all"
if [[ $# -gt 0 ]]; then
    COMMAND="$1"
fi

case "$COMMAND" in
    help|-h|--help)
        print_help
        ;;
    check)
        run_check
        ;;
    package)
        run_package
        ;;
    all)
        run_check
        run_package
        ;;
    *)
        echo "Unknown command: $COMMAND" >&2
        print_help
        exit 1
        ;;
esac
