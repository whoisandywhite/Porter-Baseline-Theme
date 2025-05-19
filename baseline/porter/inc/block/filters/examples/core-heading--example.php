<?php 
class baseline_filterHeadingExample {

    private $blockName = "core/heading";
    private $className = "example"; // optional

    public function __construct() 
    {
        // Add a filter to modify the output of the core/post-featured-image block
        add_filter('render_block', array($this, 'filter_block_content'), 10, 2);
    }

    /**
     * Filter callback to modify the output of the block.
     *
     * @param string $block_content The block content.
     * @param array $block The block data.
     * @return string Modified block content.
     */
    public function filter_block_content($block_content, $block) 
    {
        if( !isset( $block['blockName'] ) || $block['blockName'] !== $this->blockName ) {
            return $block_content;
        }
        if( !empty( $this->className ) && 
            ( !isset( $block['attrs']['className'] ) || strpos( $block['attrs']['className'], $this->className ) === false) ) 
        {
            return $block_content;
        }

        // Filter $block_content here, probably with preg_match or DOM manipulation

        return $block_content;
    }
}

// Instantiate the class
// new baseline_filterHeadingExample();
