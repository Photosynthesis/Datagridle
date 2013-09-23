Datagridle
==========

###Readme
Datagridle is a rapidly-deployable, feature-rich and customizable open-source database content editing system. It is built to be embedded as an admin interface in other applications, or used as a stand-alone database editor.

Please send bug reports and feature requests by email to adam@photosynth.ca, or on our Github repo at https://github.com/Photosynthesis/Datagridle

For documentation, please see [http://www.photosynth.ca/code/datagridle/docs](http://www.photosynth.ca/code/datagridle/docs).

###Ultra-rapid development: create a full-featured DB interface with just a few lines of code.
Using just the table name and DB info, Datagridle can create a feature-packed DB backend interface for use in custom applications and modules.

###Super embeddable
Datagridle is designed to be seamlessly embedded into other applications. It uses unique GET prefixes for URL variables to eliminate conflicts within an application, and preserves all existing URL variables. This makes it a breeze to integrate into existing applications, plugins, or modules. HTML headers can be turned on or off, enabling either stand-alone or embedded operation.

###Highly customizable
- Define callback functions for display, editing, or saving for any specific field
- Allow or deny editing, adding, or deleting records with permissions settings
- Use template display types with data placeholders to display images, hyperlinks, and more in the grid
- Specify style information for edit and display of individual fields
- Built-in TinyMCE wysiwyg content editor
- Advanced form fields such as calendar date selector and multiple checkbox

###Multi-table capable
Parent and child table relationships can be defined to allow editing of child records through a pop-out sub-grid, and population of descriptive fields from a parent table into foreign key fields.

###Comes with standard features too
Compact search, pagination and sorting functions give your users control of the grid.

###Caveats
Your application must take care of user control and authentication.
Datagridle has not been tested on very large data sets, and has not been optimized for this. If you use it for editing a large table (> 10,000 records) please let us know, and report any performance issues.

