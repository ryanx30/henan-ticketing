module.exports = {
  apps: [
    {
      name: "henan-schedule",
      script: "artisan",
      interpreter: "php",
      args: "schedule:work",
      autorestart: true,
      watch: false,
    },
    {
      name: "henan-queue",
      script: "artisan",
      interpreter: "php",
      args: "queue:work --queue=exports,default --sleep=1 --timeout=600 --tries=2",
      autorestart: true,
      watch: false,
    }
  ]
};