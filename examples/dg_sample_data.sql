
--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(10) NOT NULL auto_increment,
  `name` varchar(250) NOT NULL,
  `blurb` text NOT NULL,
  `image` varchar(250) NOT NULL,
  `date` varchar(250) NOT NULL,
  PRIMARY KEY  (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `blurb`, `image`, `date`) VALUES
(2, 'Lectures', 'Test blurbiage', 'test image', '2014-09-09'),
(4, 'Witicisms', 'This is the category blurb', 'some image', '2012-12-12');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `comment_id` int(10) NOT NULL auto_increment,
  `post_id` int(10) NOT NULL,
  `title` varchar(250) NOT NULL,
  `text` text NOT NULL,
  `user_id` int(10) NOT NULL,
  `date` varchar(250) NOT NULL,
  PRIMARY KEY  (`comment_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=14 ;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `title`, `text`, `user_id`, `date`) VALUES
(1, 1, 'The Commento', 'Comment text text text', 123, '2014-09-09'),
(2, 1, 'Another comment', 'Other comment text', 123, '2012-12-12'),
(6, 5, 'Five post ', 'tretst', 22, 'ewrt'),
(7, 1, 'The Comment copy', 'Comment text text text (three times)', 123, '2014-09-09'),
(8, 1, 'New new commentasfasdfsadf', 'Text and stuff', 123, '2013-01-01'),
(9, 1, 'The Comment copy', 'Comment text text text (three times)', 123, '2014-09-09'),
(10, 3, 'This is a nice comment', 'The comment content edited', 1234, '123'),
(11, 4, 'Super test comment', 'text', 123123, '213123'),
(12, 3, 'The Commento 2', 'Comment text text text', 123, '2014-09-09'),
(13, 3, 'This is a very nice comment', 'The comment content edited2', 1234, '123');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `post_id` int(10) NOT NULL auto_increment,
  `category_id` int(10) NOT NULL,
  `section_id` int(10) NOT NULL,
  `name` varchar(250) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(250) NOT NULL,
  `url` varchar(250) NOT NULL,
  `other_function` varchar(250) NOT NULL,
  `date` varchar(250) NOT NULL,
  `selectable` varchar(250) NOT NULL,
  `password` varchar(250) NOT NULL,
  `tags` varchar(250) NOT NULL,
  PRIMARY KEY  (`post_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `category_id`, `section_id`, `name`, `content`, `image`, `url`, `other_function`, `date`, `selectable`, `password`, `tags`) VALUES
(1, 2, 2, 'Post Title', '<p>The content <strong>here</strong></p>', 'sample.jpg', 'http://www.somurl.sometld', 'Foo=bar (plus some additional text) (plus some additional text) (plus some additional text)', '2013-01-05', 'things', 'this is the pass', 'Great writing, Amazing, Etc'),
(3, 4, 23, 'Post Title #2', '<p>The content <strong>here</strong></p>', 'sample.jpg', 'http://www.somurl.sometld', 'Foo=bar (plus some additional text) (plus some additional text)', '2013-01-05', 'things', 'this is the pass', ''),
(4, 4, 23, 'Post Title123w', '<p>The content <strong>here</strong></p>', 'sample.jpg', 'http://www.somurl.sometld', 'Foo=bar (plus some additional text) (plus some additional text) (plus some additional text) (plus some additional text)', '2013-01-05', 'things', 'this is the pass', 'Great writing, Nifty, Etc'),
(5, 2, 23, 'Post Title', '<p>The content <strong>here. </strong>Indeedio!<strong><br /></strong></p>', 'sample.jpg', 'http://www.somurl.sometld', 'Foo=bar (plus some additional text) (plus some additional text) (plus some additional text) (plus some additional text) (plus some additional text) (plus some additional text) (plus some additional text)', '2013-01-17', 'things', 'this is the pass', 'Great writing, Fascinating');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE IF NOT EXISTS `sections` (
  `section_id` int(10) NOT NULL auto_increment,
  `title` varchar(250) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY  (`section_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24 ;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `title`, `description`) VALUES
(2, 'Boring stuff', 'Boring description'),
(23, 'Important stuff', 'Important description');
