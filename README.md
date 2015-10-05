# Elastification Php Backup And Restore

This app dumps data from elasticsearch into your filesystem and you can restore it from there. You can easily define 
new cases by interactive console.

---

## How it is working

A backup will create a new folder in the specified target. The new folder name will look like *20151001121014*.
That folder contains different subdirs like data, meta, schema. 

The restore will work on the previous stored backup folder. (Example: */tmp/my-backup-for-production/20151001121014*)
It is operating on stored files and starts importing everything after a short configuration
Afte every successful imported index a refresh command will be run on that index.
It makes the data available in the lucene search index. It does not store store data on disk. For that it will be better to perform a flush command by curl.

---

## Requirements

You need php to be installed in a minimum version of 5.5.

For checking it, type that in your console:

```
	php -v
```

---

## Installation



---

## Usage And Options

At the moment there are only two existing commands. 

- backup:run
- restore:run

### Backup 

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

** Sample for config based backup**

```
	./php-backup-restore.phar backup:run --config /tmp/-my-backups/20151001163526/config/backup-cfg.yml
```



### Restore

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

** Sample for config based restore**

```
	./php-backup-restore.phar restore:run --config /tmp/-my-backups/20151001163526/config/restore-cfg.yml
```


---

## Ideas

- Option for ip's will not work here. It should also be a config per index/type entry. Like an array of id's
- for backup config you could provide a query per index/type
- creating zip files when zip extension is enabled and also ask for deleting directory after created zip
- backup store aliases if set on backuped indices
- command: explore backups with showing meta data
- command: backup:config should create a config file only and stores somewhere
- WRITING TESTS !!!




