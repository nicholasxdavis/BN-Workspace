<?php
// api/integrations/config.php

// IMPORTANT: Replace with your actual domain and paths
define('ROOT_URL', 'https://workspace.blacnova.net/'); // Change this to your actual domain and workspace path

// --- Encryption Key ---
// Used to securely store tokens in the database.
// IMPORTANT: Change this to a long, random string and keep it secret!
// You can generate one here: https://www.random.org/strings/
define('ENCRYPTION_KEY', '77485802438372333823753925042623');
define('ENCRYPTION_CIPHER', 'AES-256-CBC');

// --- Reddit API Credentials ---
// IMPORTANT: Replace these with the credentials from your Reddit App
define('REDDIT_CLIENT_ID', 'k8zVPy-VzLzKS1XOETZhug');
define('REDDIT_CLIENT_SECRET', 'JqI-5k4MW7bKjZYnPaiewdPYYOfj3A');
define('REDDIT_REDIRECT_URI', ROOT_URL . 'api/integrations/callback.php');

// --- Notion API Credentials ---
// Fetches credentials from server environment variables
define('NOTION_CLIENT_ID', getenv('NOTION_ID'));
define('NOTION_CLIENT_SECRET', getenv('NOTION_KEY'));
define('NOTION_REDIRECT_URI', ROOT_URL . 'api/integrations/callback.php');

?>