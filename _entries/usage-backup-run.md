---
sectionid: usage-backup-run
sectionclass: h2
is-parent: yes
parent-id: usage-and-options
title: Backup Run
number: 4100
---
For creating backup you can use the **"backup:run"** argument. It requires at least *"--host your-host-here"* option
for starting an interactive console.
After a successful run of backup your data a configuration file will be written in yaml format.

**Options:**

- **config**: A path to a file in yaml format
- **host**: Elasticsearch host or ip address. This will overwrite the host in config file, config and host is used at same time
- **port**: Port where the elasticsearch api is listening. Default is 9200
- **type**: Choose between "full" and "custom" backup. Custom backup will start an interactive process. The default is custom.
- **target**: Defines a target directory where you data will be stored. (example: /tmp/my-backups) This is required for type=full


**Sample for a full backup**

```
	./php-backup-restore.phar backup:run --host localhost --type full --target /tmp/-my-backups
```

**Sample for a interactive backup**

```
	./php-backup-restore.phar backup:run --host localhost --target /tmp/-my-backups
```

**Sample for config based backup**

```
	./php-backup-restore.phar backup:run --config /tmp/-my-backups/20151001163526/config/backup-cfg.yml
```
