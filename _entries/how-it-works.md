---
sectionid: how-it-works
sectionclass: h1
title: How it works
number: 1000
---
A backup will create a new folder in the specified target. The new folder name will look like *20151001121014*.
That folder contains different subdirs like data, meta, schema.

The restore will work on the previous stored backup folder. (Example: */tmp/my-backup-for-production/20151001121014*)
It is operating on stored files and starts importing everything after a short configuration
Afte every successful imported index a refresh command will be run on that index.
It makes the data available in the lucene search index. It does not store store data on disk. For that it will be better to perform a flush command by curl.
