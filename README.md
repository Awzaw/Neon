### General
NEON is a PocketMine-MP plugin for colouring signs. Fast. Make your own presets or use a built-in scheme such as `/neon rnd` to start painting signs in one click.
### Commands
`/neon` - displays the help for Neon

`/neon list` - displays a list of all neon themes from neon.yml

`/neon [name of theme]` - activates a theme ready for sign coloring

`/neon set test color1 color2 color3 color4` - make a new theme called test, and activate it

`/neon del test` - delete the theme called test

`/neon rnd` - start coloring with random colors

`/neon color1 color2 color3 color4` - start coloring with these colors

`/neon off` - turn off Neon sign coloring

### Examples
`/neon set random RND RND RND RND` to make a new "RANDOM" theme with random colours for every letter on each line

`/neon set fancy RND GOLD RND GREEN` to make a new 'fancy' theme, 1st line random colours, 2nd Gold, 3rd Random colours, 4th line green

`/neon RND RED RED RED` to start colouring signs with 1st line random colours, 2nd, 3rd and 4th all RED

### Important
please note that colours can be in upper or lower case, but RND as a colour at the start of the pattern must be upper case to avoid confusion with `/neon rnd`

### Permissions

The `neon' permission defaults to OP only.

`neon` - players can use all neon commands

All players can use Neon in whitelisted worlds regardless of permissions - see config.yml

### Available Colours
<p>RND - use this to make every letter of the line a random colour.</p>
<p>BLACK, DARK_BLUE, DARK_GREEN, DARK_AQUA, DARK_RED, DARK_PURPLE, GOLD, GRAY, DARK_GRAY, BLUE, GREEN, AQUA, RED, LIGHT_PURPLE, YELLOW, WHITE</p>