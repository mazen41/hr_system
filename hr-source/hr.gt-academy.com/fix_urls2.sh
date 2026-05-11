#!/bin/bash
cd /var/www/hr.gt-academy.com

# Fix remaining patterns with space after colon
sed -i 's|url: "./hr-app/|url: "hr-app/index.php?action=|g' Hrdashboard.php holidays-add.php user-certifacte.php user-experince.php

echo "Remaining URL fixes applied"
