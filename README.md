## Ideas


- Option for ip's will not work here. It should also be a config per index/type entry. Like an array of id's
- for backup config you could provide a query per index/type
- creating zip files when zip extension is enabled and also ask for deleting directory after created zip
- backup store aliases if set on backuped indices
- command: explore backups with showing meta data
- command: backup:config should create a config file only and stores somewhere





## Sample Commands

./elastic-backup-restore restore:run --host localhost --source /tmp/my-backups/20150924151250

./elastic-backup-restore backup:run --host 192.168.33.144 --port 9200 --target /tmp/my-backups

./elastic-backup-restore restore:run --config /tmp/my-backups/20150928082014/config/20150928152350_restore.yml



