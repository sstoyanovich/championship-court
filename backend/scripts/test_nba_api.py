# Source - https://stackoverflow.com/a
# Posted by e-motta, modified by community. See post 'Timeline' for change history
# Retrieved 2025-12-14, License - CC BY-SA 4.0

from collections import defaultdict

# rest of the code omitted...

todayGames = defaultdict(list)

for game in games:
    todayGames["GAME_ID"].append(game["gameId"])
    todayGames["AWAY"].append(game["awayTeam"]["teamTricode"])
    todayGames["HOME"].append(game["homeTeam"]["teamTricode"])

df = pd.DataFrame(todayGames)
