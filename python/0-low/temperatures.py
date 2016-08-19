import sys

debug_default = True


def d(msg, debug: bool = debug_default):
    if debug:
        print(msg, file=sys.stderr)


def closest_to_0(current_closest: int, new_value: int):
    if abs(new_value) < abs(current_closest):
        return new_value
    if abs(current_closest) < abs(new_value):
        return current_closest

    if current_closest < new_value:
        return new_value

    return current_closest


n = int(input())  # the number of temperatures to analyse
temps = [int(i) for i in input().split()]  # the n temperatures expressed as integers ranging from -273 to 5526

try:
    closest = temps[0]
    for i in range(1, len(temps)):
        d(temps[i])
        closest = closest_to_0(closest, temps[i])
except IndexError:
    closest = 0

print(closest)
