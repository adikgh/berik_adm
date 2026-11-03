<?php
// Claude (Anthropic API) және Google Drive баптаулары.
// Осы файлды config/ai.php деп көшіріп, мәндерін толтырыңыз. config/ai.php git-ке кірмейді (құпия).

// Anthropic API кілті: https://console.anthropic.com → API Keys
const ANTHROPIC_API_KEY = '';
const CLAUDE_MODEL = 'claude-opus-5-5';

// Google Drive (OAuth). Google Cloud Console → APIs & Services → Credentials → OAuth client ID.
// Локалда (adm.berik.c): «Desktop app» түрі, GDRIVE_REDIRECT = 'http://localhost'.
// Live-та (https домен): «Web application» түрі, GDRIVE_REDIRECT = 'https://<админ домені>/files/drive.php'.
const GDRIVE_CLIENT_ID = '';
const GDRIVE_CLIENT_SECRET = '';
const GDRIVE_REDIRECT = 'http://localhost';
// Drive-тағы негізгі папка аты (сайт өзі жасайды)
const GDRIVE_ROOT = 'Berik POS';
