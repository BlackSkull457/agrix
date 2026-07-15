#!/bin/bash

# Ensure only mpm_prefork is loaded
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* || true

# Start Apache
exec apache2-foreground
