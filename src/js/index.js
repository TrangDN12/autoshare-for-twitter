import { Component } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { PluginPrePublishPanel, PluginPostPublishPanel, PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { dispatch, select, subscribe } from '@wordpress/data';
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createAutoshareStore, STORE } from './store';
import { getIconByStatus } from './utils';
import AutoshareForTwitterPrePublishPanel from './AutoshareForTwitterPrePublishPanel';
import AutoshareForTwitterPostStatusInfo from './AutoshareForTwitterPostStatusInfo';

import EnabledIcon from '../../assets/images/twitter_enabled.svg';
import DisabledIcon from '../../assets/images/twitter_disabled.svg';

createAutoshareStore();

class AutoshareForTwitterPrePublishPanelPlugin extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			enabledText: '',
		};

		this.maybeSetEnabledText = this.maybeSetEnabledText.bind( this );
	}

	componentDidMount() {
		dispatch( STORE ).setLoaded();
		subscribe( this.maybeSetEnabledText );
	}

	maybeSetEnabledText() {
		try {
			const enabled = select( STORE ).getAutoshareEnabled();
			const enabledText = enabled ? __( 'This post will be Tweeted', 'autoshare-for-twitter' ) : __( 'This post will not be Tweeted', 'autoshare-for-twitter' );

			if ( enabledText !== this.state.enabledText ) {
				this.setState( { enabled, enabledText } );
			}
		} catch ( e ) {}
	}

	render() {
		const { enabled, enabledText } = this.state;
		const PluginIcon = enabled ? EnabledIcon : DisabledIcon;
		const AutoTweetIcon = (
			<Icon
				className="autoshare-for-twitter-icon components-panel__icon"
				icon={ <PluginIcon /> }
				size={ 24 }
			/>
		);

		return (
			<PluginPrePublishPanel
				title={ enabledText }
				icon={ AutoTweetIcon }
				className="autoshare-for-twitter-pre-publish-panel"
			>
				<AutoshareForTwitterPrePublishPanel />
			</PluginPrePublishPanel>
		);
	}
}

const AutoshareForTwitterPostPublishPanelPlugin = () => {
	return (
		<PluginPostPublishPanel className="my-plugin-post-status-info">
			<AutoshareForTwitterPostStatusInfo />
		</PluginPostPublishPanel>
	);
};

class AutoshareForTwitterEditorPanelPlugin extends Component {
	constructor( props ) {
		super( props );

		this.state = {
			enabledText: '',
		};

		this.maybeSetEnabledText = this.maybeSetEnabledText.bind( this );
	}

	componentDidMount() {
		dispatch( STORE ).setLoaded();
		subscribe( this.maybeSetEnabledText );
	}

	maybeSetEnabledText() {
		try {
			const enabled = select( STORE ).getAutoshareEnabled();
			const enabledText = enabled ? __( 'Autotweet enabled', 'autoshare-for-twitter' ) : __( 'Autotweet disabled', 'autoshare-for-twitter' );

			if ( enabledText !== this.state.enabledText ) {
				this.setState( { enabledText, enabled } );
			}
		} catch ( e ) {}
	}

	render() {
		const postStatus = select( 'core/editor' ).getCurrentPostAttribute( 'status' );
		if ( 'publish' === postStatus ) {
			const tweetMeta = select( 'core/editor' ).getCurrentPostAttribute( 'autoshare_for_twitter_status' );
			let tweetStatus = '';
			if ( tweetMeta && tweetMeta.message && tweetMeta.message.length ) {
				tweetStatus = tweetMeta.message[ tweetMeta.message.length - 1 ].status || '';
			}

			return (
				<PluginDocumentSettingPanel
					title={ __( 'Autotweet', 'autoshare-for-twitter' ) }
					icon={ getIconByStatus( tweetStatus ) }
					className="autoshare-for-twitter-editor-panel"
				>
					<AutoshareForTwitterPostStatusInfo />
				</PluginDocumentSettingPanel>
			);
		}

		const { enabled, enabledText } = this.state;
		const PluginIcon = enabled ? EnabledIcon : DisabledIcon;
		const AutoTweetIcon = (
			<Icon
				className="autoshare-for-twitter-icon"
				icon={ <PluginIcon /> }
				size={ 24 }
			/>
		);

		return (
			<PluginDocumentSettingPanel
				title={ enabledText }
				icon={ AutoTweetIcon }
				className="autoshare-for-twitter-editor-panel"
			>
				<AutoshareForTwitterPrePublishPanel />
			</PluginDocumentSettingPanel>
		);
	}
}

registerPlugin( 'autoshare-for-twitter-editor-panel', { render: AutoshareForTwitterEditorPanelPlugin } );
registerPlugin( 'autoshare-for-twitter-pre-publish-panel', { render: AutoshareForTwitterPrePublishPanelPlugin } );
registerPlugin( 'autoshare-for-twitter-post-publish-panel', { render: AutoshareForTwitterPostPublishPanelPlugin } );
