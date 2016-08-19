import sys
import math

# Auto-generated code below aims at helping you parse
# the standard input according to the problem statement.

road = int(input())  # the length of the road before the gap.
gap = int(input())  # the length of the gap.
platform = int(input())  # the length of the landing platform.

# game loop
while True:
    speed = int(input())  # the motorbike's speed.
    coord_x = int(input())  # the position on the road of the motorbike.

    if coord_x >= road + gap:
        print('SLOW')
        continue

    # If gap is behind us.
    if coord_x + speed > road:
        print('JUMP')
        continue

    # If still before the gap and not fast enough.
    if speed < gap + 1:
        print('SPEED')
        continue

    # If still before the gap and to fast.
    if speed > gap + 1:
        print('SLOW')
        continue

    print('WAIT')
