#!/bin/bash
cd /var/www/hr.gt-academy.com

# Pattern 1: url:"./hr-app/something" -> url:"hr-app/index.php?action=something"
find . -maxdepth 1 -name "*.php" -exec sed -i 's|url:"./hr-app/|url:"hr-app/index.php?action=|g' {} \;

# Pattern 2: url: "./hr-app/something" -> url: "hr-app/index.php?action=something"
find . -maxdepth 1 -name "*.php" -exec sed -i "s|url: './hr-app/|url: 'hr-app/index.php?action=|g" {} \;

# Pattern 3: url: '/hr-app/something' -> url: 'hr-app/index.php?action=something'
find . -maxdepth 1 -name "*.php" -exec sed -i "s|url: '/hr-app/|url: 'hr-app/index.php?action=|g" {} \;

# Pattern 4: url:'/hr-app/something' -> url:'hr-app/index.php?action=something'
find . -maxdepth 1 -name "*.php" -exec sed -i "s|url:'/hr-app/|url:'hr-app/index.php?action=|g" {} \;

# Pattern 5: url: "/hr-app/something" -> url: "hr-app/index.php?action=something"
find . -maxdepth 1 -name "*.php" -exec sed -i 's|url: "/hr-app/|url: "hr-app/index.php?action=|g' {} \;

# Pattern 6: url:"/hr-app/something" -> url:"hr-app/index.php?action=something"
find . -maxdepth 1 -name "*.php" -exec sed -i 's|url:"/hr-app/|url:"hr-app/index.php?action=|g' {} \;

echo "URL fixes applied"
