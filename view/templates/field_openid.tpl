{{*
  * Copyright (C) 2010-2024, the Friendica project
  * SPDX-FileCopyrightText: 2010-2024 the Friendica project
  *
  * SPDX-License-Identifier: AGPL-3.0-or-later
  *}}
	<div class="field input openid" id="wrapper_{{$field.0}}">
		<label for="id_{{$field.0}}">{{$field.1}}</label>
		<input name="{{$field.0}}" id="id_{{$field.0}}" type="text" value="{{$field.2}}" {{if $field.4}}readonly{{/if}} aria-describedby="{{$field.0}}_tip">
		{{if $field.3}}
		<span class="field_help" role="tooltip" id="{{$field.0}}_tip">{{$field.3 nofilter}}</span>
		{{/if}}
	</div>
