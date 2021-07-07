# commit-messages
Github workflow commit message check.

```yaml
name: Checks
on: pull_request
jobs:
  commit-messages:
    name: Commits Messages
    runs-on: ubuntu-latest
    steps:
      - name: Setup repository
        run: git clone -n https://${{ github.actor }}:${{ github.token }}@github.com/${{ github.repository }}.git .
      - name: Download phar file
        run: wget https://github.com/devorto/github-actions/releases/latest/download/github_actions.phar
      - name: Make executable
        run: chmod +x github_actions.phar
      - name: Run check
        run: ./github_actions.phar commit-messages
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```
