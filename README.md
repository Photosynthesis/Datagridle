###Readme
Datagridle is a rapidly-deployable, feature-rich and customizable open-source database content editing system. It is built to be embedded as an admin interface in other applications, or used as a stand-alone database editor.

Please send bug reports and feature requests by email to adam@photosynth.ca, or on our Github repo at https://github.com/Photosynthesis/Datagridle

####Features

-Fast: set up a database content editing grid with a few lines of code
-Multi-table capable
-Define save, edit, and display callbacks for arbitrary customization
-WYSIWYG editor
-Heuristic setup based on database table features
-12 pre-defined field types including calendar date picker and select boxes populated from other database tables
-Unique url string prefixes allow painless embedding in other aplications
-Use template display types with data placeholders to display images, hyperlinks, and more in the grid
-Specify style information for edit and display of individual fields
-Specify edit, add, and delete privileges
-Users can hide columns in the grid display for convenient browsing
-Sort, search, and pagination


####Sample usage

include('datagrid.class.php');

$grid = new datagrid('pages');

echo $grid->grid();
