# WordPress Searchin'custom Plugin
Plugin per form di ricerca che fanno capo a custom field ACF

## Visualizzazione del form
`[cabi_searching_custom_form post_type="my_custom_post_slug" fields="acf_field1, acf_field2" landing="result_page_slug"]`

## Visualizzazione dei risultati di ricerca
`[cabi_searching_custom_results template="template_filename" compares="eq, eq" container_class="my_custom_class"]`

Il parametro *compares* contiene le condizioni di ricerca per i campi inseriti nel form:
* `lk: LIKE`
* `eq: =`
* `gt: >`
* `ge: >=`
* `lt: <`
* `le: <=`