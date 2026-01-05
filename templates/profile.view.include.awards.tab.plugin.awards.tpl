<!-- Awards Tab -->
{if $showAwardsOnProfile}
    <div class="tab-pane fade" id="adm_profile_awards_pane" role="tabpanel" aria-labelledby="adm_profile_awards_tab">
        <a class="admidio-icon-link float-end" href="{$urlAwardsShow}">
            <i class="bi bi-pencil-square" title="{$l10n->get('AWA_HEADLINE')}"></i>
        </a> 
         
        <table id="adm_awards_table" class="table table-hover" width="100%" style="width: 100%;">
            <tbody>
                {foreach $awardsTemplateData as $row}
                    {if !(isset($PrevCatName)) || ($PrevCatName != $row.awa_cat_name)}
                        {assign var="PrevCatName" value=$row.awa_cat_name}
                        <tr >
                            <th colspan="4">{$row.awa_cat_name}</th>      
                        </tr>
                    {/if}
                                    
                    <tr id="row_{$row.id}">
                       <td style="word-break: break-word;">{$row.awa_text}</td>
                        <td class="text-end">
                            {$row.awa_text_date}
                            {include 'sys-template-parts/list.functions.tpl' data=$row}    
                        </td>
                    </tr>
                    
                {/foreach}
            </tbody>
        </table>
    </div>
{/if}
