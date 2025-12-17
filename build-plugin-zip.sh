#!/bin/bash
#
# Build script for Region Manager WordPress Plugin
# This script creates a properly structured ZIP file for WordPress plugin installation
#

# Exit on error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Plugin details
PLUGIN_SLUG="region-manager"
VERSION=$(grep "Version:" region-manager/region-manager.php | awk '{print $3}')
BUILD_DIR="build"
DIST_DIR="dist"

echo -e "${GREEN}===========================================${NC}"
echo -e "${GREEN}Region Manager Plugin Build Script${NC}"
echo -e "${GREEN}===========================================${NC}"
echo ""
echo -e "Plugin: ${YELLOW}${PLUGIN_SLUG}${NC}"
echo -e "Version: ${YELLOW}${VERSION}${NC}"
echo ""

# Clean previous builds
echo -e "${YELLOW}[1/4] Cleaning previous builds...${NC}"
rm -rf "${BUILD_DIR}"
rm -rf "${DIST_DIR}"
mkdir -p "${BUILD_DIR}"
mkdir -p "${DIST_DIR}"

# Copy plugin files to build directory
echo -e "${YELLOW}[2/4] Copying plugin files...${NC}"
cp -r region-manager "${BUILD_DIR}/${PLUGIN_SLUG}"

# Clean up development files from build
echo -e "${YELLOW}[3/4] Cleaning development files...${NC}"
cd "${BUILD_DIR}/${PLUGIN_SLUG}"

# Remove development files and directories if they exist
rm -rf .git .github .gitignore
rm -rf node_modules
rm -rf tests
rm -f .editorconfig .phpcs.xml phpunit.xml composer.lock package-lock.json
rm -f *.log

cd ../..

# Create ZIP file
echo -e "${YELLOW}[4/4] Creating ZIP archive...${NC}"
cd "${BUILD_DIR}"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"
zip -r "../${DIST_DIR}/${ZIP_NAME}" "${PLUGIN_SLUG}" -q

cd ..

# Verify the ZIP structure
echo ""
echo -e "${GREEN}===========================================${NC}"
echo -e "${GREEN}Build Complete!${NC}"
echo -e "${GREEN}===========================================${NC}"
echo ""
echo -e "ZIP file created: ${GREEN}${DIST_DIR}/${ZIP_NAME}${NC}"
echo ""
echo -e "${YELLOW}Verifying ZIP structure...${NC}"
unzip -l "${DIST_DIR}/${ZIP_NAME}" | head -20
echo ""
echo -e "${GREEN}✓ The ZIP file is ready for WordPress installation!${NC}"
echo -e "${GREEN}✓ Upload this file via WordPress Admin > Plugins > Add New > Upload Plugin${NC}"
echo ""
