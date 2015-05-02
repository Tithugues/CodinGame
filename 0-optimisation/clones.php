<?
//http://labs.builtbyprime.com/tinyphp/
list($nbFloors,,,$exitFloor,$exitPos,,,$nbElevators)=fscanf(STDIN, "%d %d %d %d %d %d %d %d");
for ($i = 0; $i++ < $nbElevators;)
    fscanf(STDIN, "%d %d", $elevatorFloor,$elevatorPos)|$elevators[$elevatorFloor] = $elevatorPos;

for(;;)
    echo fscanf(STDIN, "%d %d %s", $cloneFloor, $clonePos, $direction) & $clonePos >= ($elevatorPos = $cloneFloor == $exitFloor ? $exitPos : $elevators[$cloneFloor]) & 'L' == $direction{0} | $clonePos <= $elevatorPos & 'R' == $direction{0} | $cloneFloor < 0 ? "WAIT\n" : "BLOCK\n";