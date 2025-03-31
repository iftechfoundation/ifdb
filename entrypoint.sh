#!/bin/sh -e

APP_ROOT=/var/www/html
VENDOR_IMAGE_SRC=/opt/vendor
VENDOR_LINK_TARGET=${APP_ROOT}/vendor

# Check if the target vendor path exists AND is not already the symlink we want.
# This prevents errors if the container restarts
if [ ! -L "${VENDOR_LINK_TARGET}" ] || [ "$(readlink -f ${VENDOR_LINK_TARGET})" != "${VENDOR_IMAGE_SRC}" ]; then

  if [ -e "${VENDOR_LINK_TARGET}" ]; then
    echo "Warning: Removing existing file/directory at ${VENDOR_LINK_TARGET} to create vendor symlink."
    rm -rf "${VENDOR_LINK_TARGET}"
  fi

  echo "Creating symlink: ${VENDOR_LINK_TARGET} -> ${VENDOR_IMAGE_SRC}"
  ln -s "${VENDOR_IMAGE_SRC}" "${VENDOR_LINK_TARGET}"
else
  echo "Vendor symlink already exists and is correct."
fi

# Execute the original command passed to the container (e.g., apache2-foreground)
exec "$@"
