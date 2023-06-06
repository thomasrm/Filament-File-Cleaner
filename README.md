This is a simple Laravel command, aiming to remove all unused files. I wrote this to solve an issue with Filament where files deletion is not handled when a file is detached from a model, unless the file handling logic has been implemented (as it should be)

# Installation

Add the file to app/Console/Commands

# Run

<code>php artisan app:clean-files</code>

# Warning

This was initially written for personal projects, it wasn't tested thoroughly. If you decide to go ahead and try it out, please do with caution!
