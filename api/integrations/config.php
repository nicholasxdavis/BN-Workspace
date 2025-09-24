<?php
// api/integrations/config.php

// IMPORTANT: Replace with your actual domain and paths
define('ROOT_URL', 'https://workspace.blacnova.net/'); // Change this to your actual domain and workspace path

// --- Encryption Key ---
define('ENCRYPTION_KEY', '77485802438372333823753925042623');
define('ENCRYPTION_CIPHER', 'AES-256-CBC');

// --- Reddit API Credentials ---
define('REDDIT_CLIENT_ID', 'k8zVPy-VzLzKS1XOETZhug');
define('REDDIT_CLIENT_SECRET', 'JqI-5k4MW7bKjZYnPaiewdPYYOfj3A');
define('REDDIT_REDIRECT_URI', ROOT_URL . 'api/integrations/callback.php');

// --- Notion API Credentials ---
define('NOTION_CLIENT_ID', getenv('NOTION_ID'));
define('NOTION_CLIENT_SECRET', getenv('NOTION_KEY'));
define('NOTION_REDIRECT_URI', ROOT_URL . 'api/integrations/callback.php');

// --- Dropbox API Credentials ---
define('DROPBOX_APP_KEY', getenv('DROPBOX_APP_KEY'));
define('DROPBOX_SECRET', getenv('DROPBOX_SECRET'));
define('DROPBOX_REDIRECT_URI', ROOT_URL . 'api/integrations/callback.php');

// --- GitHub App Credentials ---
define('GITHUB_CLIENT_ID', 'Iv1.74b09251824351a6'); // From your public app link
define('GITHUB_CLIENT_SECRET', getenv('GITHUB_CLIENT'));
define('GITHUB_REDIRECT_URI', ROOT_URL . 'api/integrations/callback.php');

?>