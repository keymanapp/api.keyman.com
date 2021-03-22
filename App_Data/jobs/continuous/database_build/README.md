# Continuous job for rebuilding database

This script is a Kudu webjob. See https://github.com/projectkudu/kudu/wiki/WebJobs for details

The script watches for .data/MUST_REBUILD to be created. If it is present, then creates
.data/BUILDING, deletes .data/MUST_REBUILD, and starts a database rebuild. When it is
finished, it deletes .data/BUILDING, and exits the script. Kudu will restart the script
within one minute of it exiting.

Logs are visible in Kudu interface. If the database is already building, this will
trigger a rebuild a few seconds after the current rebuild finishes. There is a
theoretical race if two separate rebuild events are received within a fraction of
a second of each other -- but the likelihood of this causing data mismatch is near
zero, as by the time the file downloads start, the source data will already be
ready.