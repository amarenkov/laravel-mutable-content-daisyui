@props([
    'text' => null,
])

<div {{ $attributes->class([
    'break-words space-y-2',
    '[&_h1]:text-xl [&_h1]:font-semibold [&_h2]:text-lg [&_h2]:font-semibold [&_h3]:font-semibold [&_h4]:font-semibold',
    '[&_ul]:list-disc [&_ol]:list-decimal [&_ul]:pl-6 [&_ol]:pl-6 [&_li>ul]:mt-1 [&_li>ol]:mt-1',
    '[&_ul:has(input)]:list-none [&_ul:has(input)]:pl-1 [&_li_input]:checkbox [&_li_input]:checkbox-xs [&_li_input]:mr-2 [&_li_input]:align-middle',
    '[&_a]:link [&_a]:link-primary [&_blockquote]:border-l-4 [&_blockquote]:border-base-300 [&_blockquote]:pl-3 [&_blockquote]:opacity-80',
    '[&_code]:font-mono [&_code]:text-sm [&_:not(pre)>code]:bg-base-200 [&_:not(pre)>code]:rounded [&_:not(pre)>code]:px-1',
    '[&_pre]:bg-base-200 [&_pre]:rounded-box [&_pre]:p-3 [&_pre]:overflow-x-auto',
    '[&_table]:table [&_table]:table-sm [&_hr]:border-base-300',
]) }}>{{ \Amarenkov\MutableContentDaisyUi\Helpers\MarkdownHelper::toHtml($text) }}</div>
