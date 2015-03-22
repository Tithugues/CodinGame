read N
for (( i=0; i<N; i++ )); do
    read Pi
    powers[i]=$Pi
done

readarray -t sPowers < <(for a in "${powers[@]}"; do echo "$a"; done | sort -n)

diffMin=-1
prev=-1
for a in ${sPowers[@]}; do
    if [[ $prev -eq -1 ]]; then
        prev=$a
        continue
    fi
    diffTmp=$(($a-$prev))
    if [[ $diffMin -eq -1 ]] || [[ $diffTmp -lt $diffMin ]]; then
        diffMin=$diffTmp
    fi
    prev=$a
done

echo $diffMin
