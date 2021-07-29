# commit-messages
Github workflow commit message check.

```yaml
name: Checks
on: pull_request
jobs:
  job-1:
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
  job-2:
    name: Branch name convention
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
          branch_regex: '/^(feature|styling|bugfix)\/[0-9]+-[a-z0-9-]+$/'
          decline_message: |
            Invalid branch name.

            Format should be: type/issue(or-ticket-number)-short-description
            Type is one of the following: feature/bugfix/styling.
            Short description should be lowercase a-z, 0-9 and - only.
```
