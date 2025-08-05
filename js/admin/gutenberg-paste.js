/**
 * Gutenberg Paste
 *
 * This script handles pasting images, cleaning HTML, embedding URLs, and pasting code directly into the Gutenberg editor.
 */
( function( wp, settings ) {
    if ( ! wp || ! wp.data || ! wp.blocks || ! wp.blob || ! wp.i18n ) {
        return;
    }

    const { createBlock, rawHandler } = wp.blocks;
    const { dispatch } = wp.data;
    const { __ } = wp.i18n;

    const debounce = ( func, wait ) => {
        let timeout;
        return function ( ...args ) {
            const context = this;
            clearTimeout( timeout );
            timeout = setTimeout( () => func.apply( context, args ), wait );
        };
    };

    const cleanHtml = ( html ) => {
        const tempDiv = document.createElement( 'div' );
        tempDiv.innerHTML = html;
        tempDiv.querySelectorAll( '*' ).forEach( el => {
            el.removeAttribute( 'style' );
            el.removeAttribute( 'class' );
            el.removeAttribute( 'id' );
        } );
        tempDiv.querySelectorAll( 'span, font' ).forEach( el => {
            const parent = el.parentNode;
            while ( el.firstChild ) {
                parent.insertBefore( el.firstChild, el );
            }
            parent.removeChild( el );
        } );
        return tempDiv.innerHTML;
    };

    /**
     * Basic detection for code based on indentation and multiple lines.
     *
     * @param {string} text The text to check.
     * @return {boolean} True if the text appears to be code.
     */
    const isCode = ( text ) => {
        const lines = text.split( /\r?\n/ );
        if ( lines.length < 2 ) {
            return false; // Needs at least 2 lines to be considered code.
        }

        let indentedLines = 0;
        for ( const line of lines ) {
            if ( line.length > 0 && ( line.startsWith( '  ' ) || line.startsWith( '\t' ) ) ) {
                indentedLines++;
            }
        }
        // Consider it code if a significant portion of lines are indented.
        return indentedLines / lines.length > 0.5;
    };

    const onPaste = ( event ) => {
        const { items } = event.clipboardData || event.originalEvent.clipboardData;
        if ( ! items || items.length === 0 ) return;

        // 1. Handle Image Pasting (highest priority)
        const files = Array.from( items ).filter( item => item.kind === 'file' && item.type.startsWith( 'image/' ) );
        if ( files.length > 0 ) {
            event.preventDefault();
            event.stopPropagation();
            files.map( item => item.getAsFile() ).forEach( uploadImage );
            return;
        }

        const textItem = Array.from( items ).find( item => item.type === 'text/plain' );
        const htmlItem = Array.from( items ).find( item => item.type === 'text/html' );

        // 2. Handle Table Pasting
        if ( htmlItem && settings.tablePasting ) {
            htmlItem.getAsString( html => {
                const tempDiv = document.createElement( 'div' );
                tempDiv.innerHTML = html;
                const table = tempDiv.querySelector( 'table' );

                if ( table ) {
                    event.preventDefault();
                    event.stopPropagation();

                    const head = Array.from( table.querySelectorAll( 'thead tr th' ) ).map( th => ( { content: th.innerText } ) );
                    const body = Array.from( table.querySelectorAll( 'tbody tr' ) ).map( tr => ( {
                        cells: Array.from( tr.querySelectorAll( 'td' ) ).map( td => ( { content: td.innerText } ) ),
                    } ) );

                    const tableBlock = createBlock( 'core/table', {
                        head: [ { cells: head } ],
                        body: body,
                    } );

                    dispatch( 'core/block-editor' ).insertBlocks( tableBlock );
                    dispatch( 'core/notices' ).createSuccessNotice( __( 'Table pasted successfully.', 'the-paste' ), { type: 'snackbar' } );
                    return; // Stop further processing
                }
            } );
        }

        // 3. Handle Smart URL Pasting
        if ( textItem && settings.smartUrl ) {
            textItem.getAsString( text => {
                const urlPatterns = [ /https?:\/\/(www\.)?youtube\.com\/watch\?v=([\w-]+)/, /https?:\/\/youtu\.be\/([\w-]+)/, /https?:\/\/vimeo\.com\/(\d+)/, /https?:\/\/twitter\.com\/(\w+)\/status\/(\d+)/ ];
                const isEmbeddable = urlPatterns.some( regex => regex.test( text.trim() ) );

                if ( isEmbeddable ) {
                    event.preventDefault();
                    event.stopPropagation();
                    const embedBlock = createBlock( 'core/embed', { url: text.trim() } );
                    dispatch( 'core/block-editor' ).insertBlocks( embedBlock );
                    dispatch( 'core/notices' ).createSuccessNotice( __( 'URL successfully embedded.', 'the-paste' ), { type: 'snackbar' } );
                    return; // Stop further processing
                }
            } );
        }

        // 4. Handle HTML Cleanup
        if ( htmlItem && settings.htmlCleanup ) {
            htmlItem.getAsString( html => {
                const cleanedHtml = cleanHtml( html );
                if ( cleanedHtml !== html ) {
                    event.preventDefault();
                    event.stopPropagation();
                    dispatch( 'core/block-editor' ).insertBlocks( rawHandler( { HTML: cleanedHtml } ) );
                    dispatch( 'core/notices' ).createSuccessNotice( __( 'Pasted content has been cleaned.', 'the-paste' ), { type: 'snackbar' } );
                    return; // Stop further processing
                }
            } );
        }

        // 5. Handle Code Pasting
        if ( textItem && settings.codePasting ) {
            textItem.getAsString( text => {
                if ( isCode( text ) ) {
                    event.preventDefault();
                    event.stopPropagation();
                    const codeBlock = createBlock( 'core/code', { content: text } );
                    dispatch( 'core/block-editor' ).insertBlocks( codeBlock );
                    dispatch( 'core/notices' ).createSuccessNotice( __( 'Code pasted successfully.', 'the-paste' ), { type: 'snackbar' } );
                }
            } );
        }
    };

    const uploadImage = ( file ) => {
        wp.mediaUtils.uploadMedia( {
            filesList: [ file ],
            onFileChange: ( [ image ] ) => {
                if ( ! image || ! image.id ) return;
                const imageBlock = createBlock( 'core/image', { id: image.id, url: image.url, alt: image.alt } );
                dispatch( 'core/block-editor' ).insertBlocks( imageBlock );
                if ( settings.quickAttributes ) {
                    setTimeout( () => {
                        dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/block' );
                    }, 100 );
                }
                dispatch( 'core/notices' ).createSuccessNotice( __( 'Image pasted and uploaded successfully.', 'the-paste' ), { type: 'snackbar' } );
            },
            onError: ( message ) => {
                dispatch( 'core/notices' ).createErrorNotice( message, { type: 'snackbar' } );
            },
        } );
    };

    const debouncedOnPaste = debounce( onPaste, 200 );

    wp.data.subscribe( () => {
        const editor = document.querySelector( '.block-editor-writing-flow' );
        if ( editor && ! editor.dataset.pasteHandlerAdded ) {
            editor.addEventListener( 'paste', debouncedOnPaste );
            editor.dataset.pasteHandlerAdded = 'true';
        }
    } );

} )( window.wp, window.thePasteGutenberg );
