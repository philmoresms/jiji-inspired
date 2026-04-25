import sys

def check_braces(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    count = 0
    line_no = 1
    for char in content:
        if char == '{':
            count += 1
        elif char == '}':
            count -= 1
        if char == '\n':
            line_no += 1

        if count < 0:
            print(f"Unmatched closing brace at line {line_no}")
            return False

    if count > 0:
        print(f"Unmatched opening brace at end of file. Balance: {count}")
        return False

    print("Braces are balanced.")
    return True

if __name__ == "__main__":
    if len(sys.argv) > 1:
        check_braces(sys.argv[1])
    else:
        print("Usage: python check_braces.py <filepath>")
