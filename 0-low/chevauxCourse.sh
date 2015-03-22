read N
for (( i=0; i<N; i++ )); do
    read Pi
    powers[i]=$Pi
done

readarray -t sPowers < <(for a in "${powers[@]}"; do echo "$a"; done | sort -n)

diffMin=$((${sPowers[1]}-${sPowers[0]}))
prev=${sPowers[1]}
for (( a=2; a<N; a++ )); do
    diffTmp=$((${sPowers[a]}-$prev))
    if [[ $diffTmp -lt $diffMin ]]; then
        diffMin=$diffTmp
    fi
    prev=${sPowers[a]}
done

echo $diffMin
