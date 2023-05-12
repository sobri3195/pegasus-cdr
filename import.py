import json

# Read the JSON file
with open('call_history.json') as file:
    data = json.load(file)

calls = data['calls']

# Calculate call frequency
call_count = len(calls)

# Calculate total call duration
total_duration = sum(int(call['call_duration'].split(' ')[0]) for call in calls)

# Calculate average call duration
average_duration = total_duration / call_count if call_count > 0 else 0

# Display call statistics
print("Call Count:", call_count)
print("Total Duration:", total_duration, "minutes")
print("Average Duration:", average_duration, "minutes")
