#!/bin/bash

A=`date +"%m%d%y"`
echo $A

dir="/sites/shopxo/public/static/upload/images/goods/2020/"
backup="/sites/shopxo/backup/0507/images/goods/2020/"
workdir="/sites/shopxo/backup"
#backup
echo "backup origin files"
`mkdir -p $backup`
`cp -r $dir $backup`


# arr=("+1000k" "+500k" "+300k")
# level=("20%x20%" "30%x30%" "50%x50%")
arr=("+1000k" "+500k" "+300k" "+200k" "+100k" "+50k" "+30k" "+20k")
level=("15%x15%" "20%x20%" "25%x25%" "30%x30%" "40%x40%" "50%x50%" "70%x70%" "90%x90%")

all=$(cat $workdir/resize.log)
for i in ${!arr[@]}
do
  value=${arr[$i]}
  cmd=$(find $dir -regex '.*\(jpg\|JPG\|png\|PNG\|jpeg\)' -size $value)
  echo "--- resize images with size:$value ---"
  diff=$(echo ${cmd[@]} ${all[@]} ${all[@]} | tr ' ' '\n' | sort | uniq -u)
  echo "$diff"
  for img in ${diff[@]}
  do
    resize=$(convert -sampling-factor 4:2:0 -strip -quality 85 -resize ${level[$i]} $img $img)
    # echo $resize
  done
  all=$(echo ${diff[@]} ${all[@]} | uniq)
  `echo $all >> $workdir/resize.log`
  # echo "all"
  # echo "$all"
  echo "--- $value end ---"
done