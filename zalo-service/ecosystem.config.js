/**
 * PM2 ECOSYSTEM CONFIG
 * Production-ready configuration with auto-restart and monitoring
 */

module.exports = {
  apps: [
    {
      name: 'school-zalo',
      script: './server.js',
      instances: 1,
      exec_mode: 'fork',
      
      // Auto-restart configuration
      autorestart: true,
      watch: false,
      max_memory_restart: '500M',
      
      // Restart on failure
      min_uptime: '10s',
      max_restarts: 10,
      restart_delay: 4000,
      
      // Exponential backoff
      exp_backoff_restart_delay: 100,
      
      // Error handling
      error_file: './logs/error.log',
      out_file: './logs/out.log',
      log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
      merge_logs: true,
      
      // Environment
      env: {
        NODE_ENV: 'production',
        TZ: 'Asia/Ho_Chi_Minh',
        PORT: 3001
      },
      
      // Cron restart (daily at 3 AM to prevent memory leaks)
      // cron_restart: '0 3 * * *', // DISABLED
      
      // Health check
      kill_timeout: 5000,
      listen_timeout: 10000,
      
      // PM2 Plus monitoring (optional)
      instance_var: 'INSTANCE_ID',
      
      // Advanced features
      vizion: false,
      post_update: ['npm install'],
      
      // Node args
      node_args: '--max-old-space-size=512'
    },
    
    // Session Monitor (separate process)
    {
      name: 'zalo-monitor',
      script: './monitor-sessions.js',
      instances: 1,
      exec_mode: 'fork',
      
      autorestart: true,
      max_memory_restart: '100M',
      min_uptime: '10s',
      max_restarts: 5,
      restart_delay: 5000,
      
      error_file: './logs/monitor-error.log',
      out_file: './logs/monitor-out.log',
      
      env: {
        NODE_ENV: 'production',
        TZ: 'Asia/Ho_Chi_Minh'
      }
    }
  ]
};
