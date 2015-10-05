---
sectionid: usage-restore-run
sectionclass: h2
is-parent: yes
parent-id: usage-and-options
title: Restore Run
number: 4200
---
For restoring you can use the **"restore:run"** argument. It requires at least *"--host your-host-here"* option
for starting an interactive console.
The restore task will ask you, if your not using a config file, to store your current restore configuraiton into cofnig folder of given source folder.

**Options:**

- **config**: A path to a file in yaml format
- **host**: Elasticsearch host or ip address. This will overwrite the host in config file, config and host is used at same time
- **port**: Port where the elasticsearch api is listening. Default is 9200
- **source**: Defines a source directory where your backup is located. (example: /tmp/my-backups/20151001163526)

**Sample for a interactive restore**

```
	./php-backup-restore.phar restore:run --host localhost
```

**Sample for config based restore**

```
	./php-backup-restore.phar restore:run --config /tmp/-my-backups/20151001163526/config/restore-cfg.yml
```
