<!-- Awards Accordion -->
{if $showAwardsOnProfile}
    <div class="accordion-item">
        <h2 class="accordion-header" id="adm_profile_awards_accordion_heading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#adm_profile_awards_accordion" aria-expanded="false" aria-controls="adm_profile_awards_accordion">
                {$l10n->get('AWA_HEADLINE')}
            </button>
        </h2>
        <div id="adm_profile_awards_accordion" class="accordion-collapse collapse" aria-labelledby="adm_profile_awards_accordion_heading" data-bs-parent="#adm_profile_accordion">
            <div class="accordion-body">
                     
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
        </div>
    </div>
{/if}
