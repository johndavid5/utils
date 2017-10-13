#for F in `find . -type f -name "*~" -name "*node_modules*" -print`;
for F in `find . -type f -name "*~" -print | grep -v "node_modules"`;
do
	#echo "rm $F..NOT!" 
	echo "rm $F..." 
	rm $F
done
