import sys

debug_default = True


def d(msg, debug: bool = debug_default):
    if debug:
        print(msg, file=sys.stderr)


def move(thor_x: int, thor_y: int, light_x: int, light_y: int):
    direction = ''
    if thor_y < light_y:
        direction += 'S'
        thor_y += 1
    elif thor_y > light_y:
        direction += 'N'
        thor_y -= 1

    if thor_x < light_x:
        direction += 'E'
        thor_x += 1
    elif thor_x > light_x:
        direction += 'W'
        thor_x -= 1

    d(thor_x)
    d(thor_y)
    d(direction)
    return [thor_x, thor_y, direction]


# light_x: the X position of the light of power
# light_y: the Y position of the light of power
# thor_x: Thor's starting X position
# thor_y: Thor's starting Y position
light_x, light_y, thor_x, thor_y = [int(i) for i in input().split()]
d(light_x)
d(light_y)
d(thor_x)
d(thor_y)

# game loop
while True:
    remaining_turns = int(input())  # The remaining amount of turns Thor can move. Do not remove this line.
    thor_x, thor_y, direction = list(move(thor_x, thor_y, light_x, light_y))
    print(direction)
