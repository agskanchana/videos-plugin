// Simple test version of the block
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

console.log('Loading Ekwa Video Block - Simple Test Version');

registerBlockType('ekwa/video-block', {
    title: 'Ekwa Video Test',
    description: 'A test video block',
    category: 'media',
    icon: 'video-alt',

    edit: function() {
        return wp.element.createElement(
            'div',
            { style: { padding: '20px', border: '2px dashed #ccc', textAlign: 'center' } },
            'Ekwa Video Block - Test Mode'
        );
    },

    save: function() {
        return null; // Server-side rendering
    }
});

console.log('Ekwa Video Block registered successfully');
