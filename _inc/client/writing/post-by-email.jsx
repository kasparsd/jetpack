/**
 * External dependencies
 */
import React from 'react';
import { connect } from 'react-redux';
import { translate as __ } from 'i18n-calypso';
import Button from 'components/button';
import ClipboardButtonInput from 'components/clipboard-button-input';

/**
 * Internal dependencies
 */
import {
	FormFieldset,
	FormLegend,
	FormLabel
} from 'components/forms';
import { ModuleToggle } from 'components/module-toggle';
import { getModule } from 'state/modules';
import { isModuleFound as _isModuleFound } from 'state/search';
import { ModuleSettingsForm as moduleSettingsForm } from 'components/module-settings/module-settings-form';
import SettingsCard from 'components/settings-card';
import SettingsGroup from 'components/settings-group';

const PostByEmail = moduleSettingsForm(
	React.createClass( {

		regeneratePostByEmailAddress( event ) {
			event.preventDefault();
			this.props.regeneratePostByEmailAddress();
		},

		address() {
			const currentValue = this.props.getOptionValue( 'post_by_email_address' );
			// If the module Post-by-email is enabled BUT it's configured as disabled
			// Its value is set to false
			if ( currentValue === false ) {
				return '';
			}
			return currentValue;
		},

		render() {
			const postByEmail = this.props.getModule( 'post-by-email' ),
				isPbeActive = this.props.getOptionValue( 'post-by-email' ),
				disabledControls = this.props.isUnavailableInDevMode( 'post-by-email' ) || ! this.props.isLinked;

			if ( ! this.props.isModuleFound( 'post-by-email' ) ) {
				return null;
			}

			return (
				<SettingsCard
					{ ...this.props }
					module="post-by-email"
					hideButton>
					<SettingsGroup hasChild disableInDevMode module={ postByEmail }>
						<ModuleToggle
							slug="post-by-email"
							compact
							disabled={ disabledControls }
							activated={ isPbeActive }
							toggling={ this.props.isSavingAnyOption( 'post-by-email' ) }
							toggleModule={ this.props.toggleModuleNow }
						>
						<span className="jp-form-toggle-explanation">
							{
								this.props.module( 'post-by-email' ).description
							}
						</span>
						</ModuleToggle>
						<FormFieldset>
							<FormLabel>
								<FormLegend>{ __( 'Email Address' ) }</FormLegend>
								<ClipboardButtonInput
									value={ this.address() }
									disabled={ ! isPbeActive || disabledControls }
									copy={ __( 'Copy', { context: 'verb' } ) }
									copied={ __( 'Copied!' ) }
									prompt={ __( 'Highlight and copy the following text to your clipboard:' ) }
								/>
							</FormLabel>
							<Button
								disabled={ ! isPbeActive || disabledControls }
								onClick={ this.regeneratePostByEmailAddress } >
								{ __( 'Regenerate address' ) }
							</Button>
						</FormFieldset>
					</SettingsGroup>
				</SettingsCard>
			);
		}
	} )
);

export default connect(
	( state ) => {
		return {
			module: ( module_name ) => getModule( state, module_name ),
			isModuleFound: ( module_name ) => _isModuleFound( state, module_name )
		};
	}
)( PostByEmail );
