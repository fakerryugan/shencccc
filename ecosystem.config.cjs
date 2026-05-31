const path = require('path');
const fs = require('fs');

const ROOT = __dirname;
const LOG_DIR = path.join(ROOT, 'logs');
const APP_PORT = process.env.SENCHA_RECRUITMENT_PORT || '2022';

function resolvePhp() {
  if (process.env.SENCHA_PHP && fs.existsSync(process.env.SENCHA_PHP)) {
    return process.env.SENCHA_PHP;
  }
  const candidates = [];
  for (const drive of ['D:', 'E:', 'C:', 'F:']) {
    for (const ver of ['83', '82', '81', '80', '74']) {
      candidates.push(path.join(drive, 'BtSoft', 'php', ver, 'php.exe'));
    }
  }
  candidates.push('C:\\xampp\\php\\php.exe', 'php');
  for (const c of candidates) {
    if (c === 'php' || fs.existsSync(c)) return c;
  }
  return 'php';
}

if (!fs.existsSync(LOG_DIR)) {
  fs.mkdirSync(LOG_DIR, { recursive: true });
}

module.exports = {
  apps: [
    {
      name: 'sencha-recruitment',
      cwd: ROOT,
      script: resolvePhp(),
      args: ['-S', `0.0.0.0:${APP_PORT}`, '-t', '.', 'router.php'],
      interpreter: 'none',
      windowsHide: true,
      watch: false,
      autorestart: true,
      max_memory_restart: '384M',
      restart_delay: 2000,
      env: {
        SENCHA_RECRUITMENT_PORT: APP_PORT,
        PHP_CLI_SERVER_WORKERS: '10',
      },
      error_file: path.join(LOG_DIR, 'pm2-error.log'),
      out_file: path.join(LOG_DIR, 'pm2-out.log'),
      log_date_format: 'YYYY-MM-DD HH:mm:ss',
    },
  ],
};
