#!/bin/bash 

startDir=`pwd`

ts=`date +"%Y-%m-%d"`
def="/home1/joylearn/site/ihds/.ihds-dump.cnf"
db="joylearn_ihds_donors"
sqlFile="${db}_${ts}.sql"
mailTo="aelster@irvinehebrewday.org"
subject="IHDS Backup - ${ts}"

cd /home1/joylearn/site/tmp

mysqldump \
    --defaults-extra-file=$def \
    --compact \
    --no-tablespaces \
    --add-drop-table \
    --skip-comments \
    --skip-extended-insert \
    $db > $sqlFile

tar -cjf ${sqlFile}.bz2 $sqlFile

echo "" | mail -a ${sqlFile}.bz2 -s "${subject}" $mailTo

echo "Mail sent - $subject"

cd $startDir