@extends('layouts.mayor')

@section('title', $project->title)
@section('topbar-title', 'Projetos')

@push('styles')
    <style>
        .project-show-page {
            padding: 1.55rem 2rem 2.2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-show-header {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 1.3rem 1.4rem;
            display: flex;
            justify-content: space-between;
            gap: 1.2rem;
            align-items: flex-start;
        }

        .project-show-header h1 {
            font-family: 'Lora', serif;
            font-size: 1.45rem;
            color: var(--ink);
            margin-bottom: .35rem;
        }

        .project-show-header p {
            font-size: .84rem;
            color: var(--ink-soft);
            line-height: 1.65;
            max-width: 820px;
        }

        .project-show-chip-row {
            display: flex;
            flex-wrap: wrap;
            gap: .45rem;
            margin-bottom: .65rem;
        }

        .project-chip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .22rem .58rem;
            border-radius: 999px;
            font-size: .67rem;
            font-weight: 700;
            letter-spacing: .04em;
            background: var(--surface);
            color: var(--ink-soft);
            border: 1px solid var(--border);
            white-space: nowrap;
        }

        .project-status {
            background: rgba(184, 144, 42, .12);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .18);
        }

        .project-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 1rem;
            align-items: start;
        }

        .project-main {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .85rem;
        }

        .project-summary-card,
        .project-side-card,
        .project-sections-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
        }

        .project-summary-card {
            padding: .95rem 1rem;
        }

        .project-summary-card strong {
            display: block;
            font-size: .7rem;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: .25rem;
        }

        .project-summary-card span {
            font-family: 'Lora', serif;
            font-size: 1.22rem;
            color: var(--ink);
        }

        .project-sections-card {
            padding: 1rem;
        }

        .project-generation-notice {
            margin-bottom: .9rem;
            padding: .9rem 1rem;
            border-radius: 14px;
            border: 1px solid rgba(184, 144, 42, .18);
            background: rgba(184, 144, 42, .08);
            color: var(--ink);
        }

        .project-generation-notice strong {
            display: block;
            font-size: .78rem;
            color: var(--gold);
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: .2rem;
        }

        .project-generation-notice span {
            display: block;
            font-size: .8rem;
            line-height: 1.6;
            color: var(--ink-soft);
        }

        .project-access-notice {
            margin-bottom: .9rem;
            padding: .85rem .95rem;
            border-radius: 14px;
            border: 1px solid rgba(26, 95, 168, .14);
            background: rgba(26, 95, 168, .08);
            color: #1a5fa8;
            font-size: .78rem;
            line-height: 1.55;
        }

        .project-questionnaire-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .project-overlap-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .project-funding-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .project-history-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .project-revisions-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .project-metadata-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1rem;
        }

        .project-funding-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .project-funding-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin-bottom: .22rem;
        }

        .project-funding-head p {
            font-size: .79rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-funding-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .project-funding-summary-item {
            background: var(--surface);
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .75rem .8rem;
        }

        .project-funding-summary-item strong {
            display: block;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
            margin-bottom: .2rem;
        }

        .project-funding-summary-item span {
            font-family: 'Lora', serif;
            font-size: 1.02rem;
            color: var(--ink);
        }

        .project-funding-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .24rem .62rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-soft);
        }

        .project-funding-status.status-strong {
            background: rgba(30, 126, 72, .1);
            color: #1e7e48;
            border-color: rgba(30, 126, 72, .16);
        }

        .project-funding-status.status-moderate {
            background: rgba(184, 144, 42, .12);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .18);
        }

        .project-funding-status.status-initial {
            background: rgba(26, 95, 168, .1);
            color: #1a5fa8;
            border-color: rgba(26, 95, 168, .14);
        }

        .project-funding-status.status-none {
            background: rgba(15, 23, 42, .05);
            color: var(--ink-soft);
            border-color: var(--border-lt);
        }

        .project-funding-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .project-funding-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .85rem .9rem;
        }

        .project-funding-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .7rem;
            margin-bottom: .35rem;
        }

        .project-funding-item-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        .project-funding-item-meta {
            font-size: .75rem;
            color: var(--ink-muted);
            margin-bottom: .45rem;
        }

        .project-funding-tags {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-bottom: .45rem;
        }

        .project-funding-tag {
            display: inline-flex;
            align-items: center;
            padding: .2rem .52rem;
            border-radius: 999px;
            font-size: .67rem;
            background: rgba(15, 23, 42, .05);
            color: var(--ink-soft);
            border: 1px solid var(--border-lt);
        }

        .project-funding-reasons {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .project-overlap-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .project-overlap-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin-bottom: .22rem;
        }

        .project-overlap-head p {
            font-size: .79rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-overlap-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .project-overlap-summary-item {
            background: var(--surface);
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .75rem .8rem;
        }

        .project-overlap-summary-item strong {
            display: block;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
            margin-bottom: .2rem;
        }

        .project-overlap-summary-item span {
            font-family: 'Lora', serif;
            font-size: 1.02rem;
            color: var(--ink);
        }

        .project-overlap-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .24rem .62rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-soft);
        }

        .project-overlap-status.status-clear {
            background: rgba(30, 126, 72, .1);
            color: #1e7e48;
            border-color: rgba(30, 126, 72, .16);
        }

        .project-overlap-status.status-attention {
            background: rgba(184, 144, 42, .12);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .18);
        }

        .project-overlap-status.status-review_required {
            background: rgba(181, 43, 43, .1);
            color: #a61f1f;
            border-color: rgba(181, 43, 43, .15);
        }

        .project-overlap-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .project-overlap-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .85rem .9rem;
        }

        .project-overlap-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .7rem;
            margin-bottom: .35rem;
        }

        .project-overlap-item-title {
            font-size: .9rem;
            font-weight: 700;
            color: var(--ink);
        }

        .project-overlap-item-meta {
            font-size: .75rem;
            color: var(--ink-muted);
            margin-bottom: .45rem;
        }

        .project-overlap-reasons {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
        }

        .project-overlap-reason {
            display: inline-flex;
            align-items: center;
            padding: .2rem .52rem;
            border-radius: 999px;
            font-size: .67rem;
            background: rgba(15, 23, 42, .05);
            color: var(--ink-soft);
            border: 1px solid var(--border-lt);
        }

        .project-questionnaire-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .project-questionnaire-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .project-questionnaire-head p {
            font-size: .79rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-questionnaire-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .project-question-progress {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .project-question-progress-item {
            background: var(--surface);
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .75rem .8rem;
        }

        .project-question-progress-item strong {
            display: block;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
            margin-bottom: .2rem;
        }

        .project-question-progress-item span {
            font-family: 'Lora', serif;
            font-size: 1.08rem;
            color: var(--ink);
        }

        .project-question-form {
            display: flex;
            flex-direction: column;
            gap: .85rem;
        }

        .project-question-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .9rem;
        }

        .project-question-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .8rem;
            margin-bottom: .35rem;
        }

        .project-question-item strong {
            font-size: .92rem;
            color: var(--ink);
        }

        .project-question-item p {
            font-size: .77rem;
            color: var(--ink-soft);
            line-height: 1.55;
            margin-bottom: .55rem;
        }

        .project-question-item textarea,
        .project-question-item input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--white);
            color: var(--ink);
            padding: .78rem .85rem;
            font-size: .86rem;
            outline: none;
        }

        .project-question-item textarea {
            min-height: 120px;
            resize: vertical;
        }

        .project-question-item textarea:focus,
        .project-question-item input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
        }

        .project-question-item-help {
            font-size: .73rem;
            color: var(--ink-muted);
            line-height: 1.5;
            margin-top: .45rem;
        }

        .project-question-form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
            margin-top: .2rem;
        }

        .project-question-note {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.6;
            max-width: 520px;
        }

        .project-metadata-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .project-metadata-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin-bottom: .22rem;
        }

        .project-metadata-head p {
            font-size: .79rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-metadata-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-metadata-group {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .95rem;
        }

        .project-metadata-group h3 {
            font-size: .9rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .project-metadata-group p {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin-bottom: .8rem;
        }

        .project-metadata-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .project-metadata-field {
            display: flex;
            flex-direction: column;
            gap: .35rem;
        }

        .project-metadata-field.full {
            grid-column: 1 / -1;
        }

        .project-metadata-field label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--ink-soft);
        }

        .project-metadata-field input,
        .project-metadata-field textarea,
        .project-metadata-field select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--white);
            color: var(--ink);
            font-size: .84rem;
            padding: .76rem .82rem;
            outline: none;
        }

        .project-metadata-field textarea {
            min-height: 120px;
            resize: vertical;
        }

        .project-metadata-field input:focus,
        .project-metadata-field textarea:focus,
        .project-metadata-field select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
        }

        .project-metadata-field input:disabled,
        .project-metadata-field textarea:disabled,
        .project-metadata-field select:disabled {
            background: #f8fafc;
            color: var(--ink-soft);
            cursor: not-allowed;
        }

        .project-metadata-help {
            font-size: .72rem;
            line-height: 1.5;
            color: var(--ink-muted);
        }

        .project-metadata-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
            flex-wrap: wrap;
        }

        .project-sections-head {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            align-items: center;
            margin-bottom: .9rem;
        }

        .project-sections-actions {
            display: flex;
            align-items: center;
            gap: .55rem;
            flex-wrap: wrap;
        }

        .project-sections-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
        }

        .project-sections-head p {
            font-size: .78rem;
            color: var(--ink-muted);
        }

        .project-sections-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .project-section-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .85rem .95rem;
            background: var(--surface);
        }

        .project-section-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .6rem;
            margin-bottom: .35rem;
        }

        .project-section-item strong {
            font-size: .9rem;
            color: var(--ink);
        }

        .project-section-item p {
            font-size: .78rem;
            color: var(--ink-soft);
            line-height: 1.55;
            margin-bottom: .35rem;
        }

        .project-section-empty {
            font-size: .77rem;
            color: var(--ink-muted);
            line-height: 1.55;
            font-style: italic;
        }

        .project-section-content {
            font-size: .8rem;
            color: var(--ink);
            line-height: 1.65;
        }

        .project-section-edit-form {
            margin-top: .75rem;
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }

        .project-section-edit-form textarea {
            width: 100%;
            min-height: 180px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--ink);
            padding: .82rem .9rem;
            font-size: .83rem;
            resize: vertical;
            outline: none;
        }

        .project-section-edit-form textarea:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
        }

        .project-section-edit-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .8rem;
            flex-wrap: wrap;
        }

        .project-section-edit-check {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            font-size: .76rem;
            color: var(--ink-soft);
        }

        .project-side {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-side-card {
            padding: 1rem;
        }

        .project-side-card h3 {
            font-family: 'Lora', serif;
            font-size: .95rem;
            color: var(--ink);
            margin-bottom: .2rem;
        }

        .project-side-card p {
            font-size: .77rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin-bottom: .8rem;
        }

        .project-side-list {
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }

        .project-side-item {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            padding: .7rem .75rem;
            background: var(--surface);
        }

        .project-side-item strong {
            display: block;
            font-size: .8rem;
            color: var(--ink);
            margin-bottom: .15rem;
        }

        .project-side-item span,
        .project-side-item small {
            display: block;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.45;
        }

        .project-side-item-actions {
            margin-top: .65rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .project-collab-form {
            display: flex;
            flex-direction: column;
            gap: .7rem;
            margin-top: .9rem;
            padding-top: .9rem;
            border-top: 1px solid var(--border-lt);
        }

        .project-collab-form select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--white);
            color: var(--ink);
            padding: .72rem .8rem;
            font-size: .84rem;
            outline: none;
        }

        .project-collab-form select:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(184, 144, 42, .08);
        }

        .project-collab-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
        }

        .project-history-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .project-history-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin-bottom: .22rem;
        }

        .project-history-head p {
            font-size: .79rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-history-list {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .project-history-note {
            margin-bottom: .8rem;
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-history-more {
            margin-top: .95rem;
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--white);
            overflow: hidden;
        }

        .project-history-more summary {
            list-style: none;
            cursor: pointer;
            padding: .85rem .95rem;
            font-size: .78rem;
            font-weight: 700;
            color: var(--ink);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
        }

        .project-history-more summary::-webkit-details-marker {
            display: none;
        }

        .project-history-more[open] summary {
            border-bottom: 1px solid var(--border-lt);
        }

        .project-history-more-body {
            padding: .9rem;
            background: var(--surface);
        }

        .project-revisions-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: .9rem;
        }

        .project-revisions-head h2 {
            font-family: 'Lora', serif;
            font-size: 1.05rem;
            color: var(--ink);
            margin-bottom: .22rem;
        }

        .project-revisions-head p {
            font-size: .79rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-revision-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
            margin-bottom: .9rem;
        }

        .project-revision-summary-item {
            background: var(--surface);
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            padding: .75rem .8rem;
        }

        .project-revision-summary-item strong {
            display: block;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
            margin-bottom: .2rem;
        }

        .project-revision-summary-item span {
            font-family: 'Lora', serif;
            font-size: 1.02rem;
            color: var(--ink);
        }

        .project-revision-compare {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .9rem;
            margin-bottom: .9rem;
        }

        .project-revision-compare-title {
            font-size: .86rem;
            color: var(--ink);
            font-weight: 700;
            margin-bottom: .25rem;
        }

        .project-revision-compare-note {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin-bottom: .75rem;
        }

        .project-revision-state-note {
            margin: 0 0 .9rem;
        }

        .project-revision-diff-group {
            display: flex;
            flex-direction: column;
            gap: .6rem;
            margin-top: .7rem;
        }

        .project-revision-diff-group h3 {
            font-size: .82rem;
            color: var(--ink);
        }

        .project-revision-diff-item {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: var(--white);
            padding: .75rem .8rem;
        }

        .project-revision-diff-item.type-added {
            border-color: rgba(30, 126, 72, .2);
            background: rgba(30, 126, 72, .05);
        }

        .project-revision-diff-item.type-removed {
            border-color: rgba(181, 43, 43, .18);
            background: rgba(181, 43, 43, .05);
        }

        .project-revision-diff-item.type-updated,
        .project-revision-diff-item.type-review_state {
            border-color: rgba(184, 144, 42, .18);
            background: rgba(184, 144, 42, .05);
        }

        .project-revision-diff-item strong {
            display: block;
            font-size: .8rem;
            color: var(--ink);
            margin-bottom: .2rem;
        }

        .project-revision-diff-item span {
            display: block;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .project-revision-diff-panels {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
            margin-top: .55rem;
        }

        .project-revision-diff-panel {
            border: 1px solid var(--border-lt);
            border-radius: 10px;
            background: var(--surface);
            padding: .65rem .7rem;
        }

        .project-revision-diff-panel strong {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ink-muted);
            margin-bottom: .3rem;
        }

        .project-revision-diff-panel span {
            font-size: .75rem;
            color: var(--ink-soft);
            line-height: 1.55;
        }

        .project-revision-diff-panel.before {
            background: rgba(181, 43, 43, .04);
        }

        .project-revision-diff-panel.after {
            background: rgba(30, 126, 72, .04);
        }

        .project-revision-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .24rem .62rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-soft);
        }

        .project-revision-status.status-draft {
            background: rgba(15, 23, 42, .05);
            color: var(--ink-soft);
            border-color: var(--border-lt);
        }

        .project-revision-status.status-approved {
            background: rgba(184, 144, 42, .12);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .18);
        }

        .project-revision-status.status-published {
            background: rgba(30, 126, 72, .1);
            color: #1e7e48;
            border-color: rgba(30, 126, 72, .16);
        }

        .project-revision-list {
            display: flex;
            flex-direction: column;
            gap: .7rem;
        }

        .project-revision-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .85rem .9rem;
        }

        .project-revision-item-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .8rem;
            margin-bottom: .4rem;
        }

        .project-revision-item-title {
            font-size: .88rem;
            color: var(--ink);
            font-weight: 700;
        }

        .project-revision-item-meta {
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        .project-revision-item-actions {
            margin-top: .6rem;
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .project-revision-status-row {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            margin-bottom: .5rem;
        }

        .project-revision-approval-card {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .9rem;
            margin-bottom: .9rem;
        }

        .project-revision-approval-card h3 {
            font-size: .86rem;
            color: var(--ink);
            margin-bottom: .25rem;
        }

        .project-revision-approval-card p {
            font-size: .76rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin-bottom: .8rem;
        }

        .project-revision-approval-list {
            display: flex;
            flex-direction: column;
            gap: .65rem;
        }

        .project-revision-approval-item {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: var(--white);
            padding: .8rem;
        }

        .project-revision-approval-item-top {
            display: flex;
            justify-content: space-between;
            gap: .7rem;
            align-items: flex-start;
            margin-bottom: .35rem;
        }

        .project-revision-approval-item strong {
            display: block;
            font-size: .82rem;
            color: var(--ink);
        }

        .project-revision-approval-item span {
            display: block;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.5;
        }

        .project-revision-approval-item select,
        .project-revision-approval-item textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--ink);
            padding: .75rem .85rem;
            font-size: .78rem;
            margin-top: .55rem;
        }

        .project-form-field {
            display: flex;
            flex-direction: column;
            gap: .45rem;
            width: 100%;
        }

        .project-form-field label {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--ink-muted);
        }

        .project-form-control,
        .project-form-textarea {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--white);
            color: var(--ink);
            padding: .8rem .9rem;
            font-size: .8rem;
            transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease;
        }

        .project-form-control:focus,
        .project-form-textarea:focus {
            outline: none;
            border-color: rgba(26, 95, 168, .28);
            box-shadow: 0 0 0 4px rgba(26, 95, 168, .08);
        }

        .project-form-textarea {
            min-height: 112px;
            resize: vertical;
            line-height: 1.6;
        }

        .project-revision-audit-note {
            border: 1px solid var(--border-lt);
            border-radius: 12px;
            background: var(--white);
            padding: .8rem;
            margin-bottom: .8rem;
        }

        .project-revision-audit-note strong {
            display: block;
            font-size: .79rem;
            color: var(--ink);
            margin-bottom: .22rem;
        }

        .project-revision-audit-note span {
            display: block;
            font-size: .75rem;
            color: var(--ink-muted);
            line-height: 1.6;
        }

        .project-revision-step-status {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .24rem .62rem;
            border-radius: 999px;
            font-size: .68rem;
            font-weight: 700;
            letter-spacing: .04em;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--ink-soft);
        }

        .project-revision-step-status.is-approved {
            background: rgba(30, 126, 72, .1);
            color: #1e7e48;
            border-color: rgba(30, 126, 72, .16);
        }

        .project-revision-step-status.is-pending {
            background: rgba(184, 144, 42, .12);
            color: var(--gold);
            border-color: rgba(184, 144, 42, .18);
        }

        .project-history-item {
            border: 1px solid var(--border-lt);
            border-radius: 14px;
            background: var(--surface);
            padding: .85rem .9rem;
        }

        .project-history-item-top {
            display: flex;
            justify-content: space-between;
            gap: .8rem;
            align-items: flex-start;
            margin-bottom: .35rem;
        }

        .project-history-item-title {
            font-size: .88rem;
            font-weight: 700;
            color: var(--ink);
        }

        .project-history-item-meta {
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.5;
            margin-bottom: .45rem;
        }

        .project-history-item-body {
            font-size: .78rem;
            color: var(--ink-soft);
            line-height: 1.6;
        }

        .project-placeholder-note {
            margin-top: .7rem;
            font-size: .74rem;
            color: var(--ink-muted);
            line-height: 1.55;
        }

        @media (max-width: 1100px) {
            .project-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .project-show-page {
                padding: 1rem;
            }

            .project-show-header,
            .project-summary-grid {
                grid-template-columns: 1fr;
                flex-direction: column;
            }

            .project-summary-grid {
                display: grid;
            }

            .project-question-progress {
                grid-template-columns: 1fr;
            }

            .project-overlap-summary {
                grid-template-columns: 1fr;
            }

            .project-funding-summary {
                grid-template-columns: 1fr;
            }

            .project-revision-summary,
            .project-revision-diff-panels,
            .project-metadata-grid,
            .project-collab-grid {
                grid-template-columns: 1fr;
            }

            .project-questionnaire-head,
            .project-question-form-actions,
            .project-funding-head,
            .project-overlap-head,
            .project-sections-head,
            .project-history-head,
            .project-revisions-head {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
@endpush

@section('content')
    <div class="project-show-page">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-warning">{{ $errors->first() }}</div>
        @endif

        <section class="project-show-header">
            <div>
                <div class="project-show-chip-row">
                    <span class="project-chip project-status">{{ $project->status_label }}</span>
                    <span class="project-chip">{{ $project->type_label }}</span>
                    <span class="project-chip">Versão {{ $project->generated_document_version }}</span>
                    <span class="project-chip">{{ $project->sections->count() }} seções</span>
                    <span class="project-chip">
                        @php
                            $roleLabel = match ($currentUserProjectRole) {
                                'owner' => 'Seu acesso: proprietario',
                                'editor' => 'Seu acesso: editor',
                                'viewer' => 'Seu acesso: visualizacao',
                                'admin' => 'Seu acesso: admin',
                                default => 'Seu acesso: restrito',
                            };
                        @endphp
                        {{ $roleLabel }}
                    </span>
                </div>
                <h1>{{ $project->title }}</h1>
                <p>{{ $project->initial_idea }}</p>
            </div>

            <a href="{{ route('mayor.projects.index') }}" class="btn btn-dark">Voltar ao painel</a>
        </section>

        <div class="project-layout">
            <div class="project-main">
                <section class="project-summary-grid">
                    <article class="project-summary-card">
                        <strong>Seções preenchidas</strong>
                        <span>{{ $filledSections }}/{{ $project->sections->count() }}</span>
                    </article>
                    <article class="project-summary-card">
                        <strong>Seções revisadas</strong>
                        <span>{{ $completedSections }}/{{ $project->sections->count() }}</span>
                    </article>
                    <article class="project-summary-card">
                        <strong>Colaboradores</strong>
                        <span>{{ $activeCollaborators->count() }}</span>
                    </article>
                    <article class="project-summary-card">
                        <strong>Ultima edição</strong>
                        <span>{{ $project->last_edited_at?->diffForHumans() ?? 'Agora' }}</span>
                    </article>
                </section>

                @if ($mandateSyncSuggestion)
                    <section class="project-metadata-card" id="project-mandate-sync">
                        <div class="project-metadata-head">
                            <div>
                                <h2>Sincronizacao com Mandato</h2>
                                <p>
                                    Este projeto foi concluido no módulo Projetos. Revise as acoes vinculadas no Mandato
                                    para decidir se o status correspondente tambem deve ser atualizado.
                                </p>
                            </div>
                            <span class="project-chip">
                                {{ $mandateSyncSuggestion['actions_to_review_count'] }} acao(oes) para revisar
                            </span>
                        </div>

                        <div class="alert alert-warning">
                            Projeto concluido com {{ $mandateSyncSuggestion['linked_actions_count'] }} acao(oes)
                            vinculada(s) no Mandato.
                        </div>

                        <div class="project-summary-grid" style="margin-top: 1rem;">
                            @foreach ($mandateSyncSuggestion['actions'] as $linkedAction)
                                <article class="project-summary-card">
                                    <strong>{{ $linkedAction['title'] }}</strong>
                                    <span>{{ $linkedAction['status_label'] }} ·
                                        {{ $linkedAction['physical_progress'] }}%</span>
                                    <span>{{ $linkedAction['axis_name'] ?: 'Sem eixo' }}</span>
                                    <a href="{{ $linkedAction['edit_url'] }}" class="btn btn-secondary">Atualizar ação</a>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="project-metadata-card" id="project-metadata" data-return-section>
                    <div class="project-metadata-head">
                        <div>
                            <h2>Metadados estruturados do projeto</h2>
                            <p>
                                Organize informacoes-chave para colaboracao, exportacao e acompanhamento. Proprietario e
                                administrador editam os dados centrais; colaboradores editor ajustam os metadados
                                operacionais.
                            </p>
                        </div>
                        <span class="project-chip">
                            {{ $canEditCoreMetadata ? 'Edição completa' : ($canEditProject ? 'Edição operacional' : 'Somente leitura') }}
                        </span>
                    </div>

                    <form method="POST" action="{{ route('mayor.projects.metadata.update', $project) }}"
                        class="project-metadata-form">
                        @csrf
                        @method('PUT')

                        <div class="project-metadata-group">
                            <h3>Dados centrais</h3>
                            <p>Controlam a identificação administrativa do projeto na prefeitura.</p>

                            <div class="project-metadata-grid">
                                <div class="project-metadata-field full">
                                    <label for="metadata-title">Título do projeto</label>
                                    <input id="metadata-title" name="title" type="text"
                                        value="{{ old('title', $project->title) }}" @disabled(!$canEditCoreMetadata)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-project-type">Tipo</label>
                                    <select id="metadata-project-type" name="project_type" @disabled(!$canEditCoreMetadata)>
                                        @foreach ($projectTypes as $value => $label)
                                            <option value="{{ $value }}" @selected(old('project_type', $project->project_type) === $value)>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-status">Status</label>
                                    <select id="metadata-status" name="status" @disabled(!$canEditCoreMetadata)>
                                        @foreach ($projectStatuses as $value => $label)
                                            <option value="{{ $value }}" @selected(old('status', $project->status) === $value)>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-secretariat">Secretaria responsável</label>
                                    <input id="metadata-secretariat" name="responsible_secretariat" type="text"
                                        value="{{ old('responsible_secretariat', $project->responsible_secretariat) }}"
                                        @disabled(!$canEditCoreMetadata)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-phase">Fase atual</label>
                                    <select id="metadata-phase" name="current_phase" @disabled(!$canEditCoreMetadata)>
                                        @foreach ($projectPhaseOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('current_phase', $project->current_phase) === $value)>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="project-metadata-field full">
                                    <label for="metadata-idea">Idéia inicial</label>
                                    <textarea id="metadata-idea" name="initial_idea" @disabled(!$canEditCoreMetadata)>{{ old('initial_idea', $project->initial_idea) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="project-metadata-group">
                            <h3>Metadados operacionais</h3>
                            <p>Informacoes estruturadas para priorizacao, captação e execução do projeto.</p>

                            <div class="project-metadata-grid">
                                <div class="project-metadata-field full">
                                    <label for="metadata-summary">Resumo executivo</label>
                                    <textarea id="metadata-summary" name="metadata[executive_summary]" @disabled(!$canEditProject)>{{ old('metadata.executive_summary', $projectMetadata['executive_summary']) }}</textarea>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-goal">Objetivo principal</label>
                                    <input id="metadata-goal" name="metadata[primary_goal]" type="text"
                                        value="{{ old('metadata.primary_goal', $projectMetadata['primary_goal']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-audience">Público beneficiado</label>
                                    <input id="metadata-audience" name="metadata[target_audience]" type="text"
                                        value="{{ old('metadata.target_audience', $projectMetadata['target_audience']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-scope">Abrangencia territorial</label>
                                    <input id="metadata-scope" name="metadata[territorial_scope]" type="text"
                                        value="{{ old('metadata.territorial_scope', $projectMetadata['territorial_scope']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-priority">Prioridade</label>
                                    <select id="metadata-priority" name="metadata[priority]" @disabled(!$canEditProject)>
                                        <option value="">A definir</option>
                                        @foreach ($projectPriorityOptions as $value => $label)
                                            <option value="{{ $value }}" @selected(old('metadata.priority', $projectMetadata['priority']) === $value)>
                                                {{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-budget">Orcamento estimado</label>
                                    <input id="metadata-budget" name="metadata[estimated_budget]" type="number"
                                        step="0.01" min="0"
                                        value="{{ old('metadata.estimated_budget', $projectMetadata['estimated_budget']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-beneficiaries">Beneficiários estimados</label>
                                    <input id="metadata-beneficiaries" name="metadata[expected_beneficiaries]"
                                        type="number" min="0"
                                        value="{{ old('metadata.expected_beneficiaries', $projectMetadata['expected_beneficiaries']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-start">Previsão de início</label>
                                    <input id="metadata-start" name="metadata[expected_start_date]" type="date"
                                        value="{{ old('metadata.expected_start_date', $projectMetadata['expected_start_date']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field">
                                    <label for="metadata-end">Previsão de conclusao</label>
                                    <input id="metadata-end" name="metadata[expected_end_date]" type="date"
                                        value="{{ old('metadata.expected_end_date', $projectMetadata['expected_end_date']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field full">
                                    <label for="metadata-funding">Estrategia de financiamento</label>
                                    <input id="metadata-funding" name="metadata[funding_strategy]" type="text"
                                        value="{{ old('metadata.funding_strategy', $projectMetadata['funding_strategy']) }}"
                                        @disabled(!$canEditProject)>
                                </div>

                                <div class="project-metadata-field full">
                                    <label for="metadata-implementation">Notas de implementacao</label>
                                    <textarea id="metadata-implementation" name="metadata[implementation_notes]" @disabled(!$canEditProject)>{{ old('metadata.implementation_notes', $projectMetadata['implementation_notes']) }}</textarea>
                                </div>

                                <div class="project-metadata-field full">
                                    <label for="metadata-risk">Riscos e cuidados</label>
                                    <textarea id="metadata-risk" name="metadata[risk_notes]" @disabled(!$canEditProject)>{{ old('metadata.risk_notes', $projectMetadata['risk_notes']) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="project-metadata-actions">
                            <div class="project-metadata-help">
                                @if ($canEditCoreMetadata)
                                    Voce pode editar toda a ficha administrativa e os metadados operacionais do projeto.
                                @elseif ($canEditProject)
                                    Voce pode editar apenas os metadados operacionais para apoiar a colaboracao e a
                                    execução.
                                @elseif ($canEditByRole ?? false)
                                    Existe uma versão final publicada como documento oficial. Abra um novo rascunho para
                                    voltar a editar os metadados operacionais.
                                @else
                                    Este perfil apenas consulta a ficha estruturada do projeto.
                                @endif
                            </div>
                            @if ($canEditProject)
                                <button type="submit" class="btn btn-dark">Salvar metadados</button>
                            @endif
                        </div>
                    </form>
                </section>

                <section class="project-questionnaire-card" id="project-questionnaire" data-return-section>
                    @unless ($canEditProject)
                        <div class="project-access-notice">
                            {{ $projectEditNotice }}
                        </div>
                    @endunless

                    <div class="project-questionnaire-head">
                        <div>
                            <h2>Perguntas dinâmicas da fase 2</h2>
                            <p>
                                O projeto ja passa por um fluxo guiado antes da geração do documento final. As perguntas
                                abaixo foram montadas conforme o tipo identificado do projeto e podem ser regeneradas se a
                                idéia base mudar.
                            </p>
                        </div>
                        <div class="project-questionnaire-actions">
                            <span class="project-chip">
                                {{ data_get($project->metadata, 'questionnaire.source', 'fallback') === 'ai' ? 'Perguntas por IA' : 'Perguntas por fallback' }}
                            </span>
                            @if ($canEditProject)
                                <form method="POST"
                                    action="{{ route('mayor.projects.questionnaire.regenerate', $project) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">Regenerar perguntas</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="project-question-progress">
                        <article class="project-question-progress-item">
                            <strong>Tipo identificado</strong>
                            <span>{{ $project->type_label }}</span>
                        </article>
                        <article class="project-question-progress-item">
                            <strong>Perguntas</strong>
                            <span>{{ $project->intakeQuestions->count() }}/8</span>
                        </article>
                        <article class="project-question-progress-item">
                            <strong>Respondidas</strong>
                            <span>{{ $answeredQuestions }}/{{ $project->intakeQuestions->count() }}</span>
                        </article>
                    </div>

                    <form method="POST" action="{{ route('mayor.projects.questionnaire.answers', $project) }}"
                        class="project-question-form">
                        @csrf

                        @foreach ($project->intakeQuestions as $question)
                            <article class="project-question-item">
                                <div class="project-question-item-top">
                                    <strong>{{ $question->question_order }}. {{ $question->question_text }}</strong>
                                    <span
                                        class="project-chip">{{ filled($question->answer) ? 'Respondida' : 'Pendente' }}</span>
                                </div>
                                <p>{{ $question->help_text ?: 'Descreva as informacoes mais objetivas possiveis para melhorar a geração do projeto.' }}
                                </p>

                                @if ($question->input_type === 'text')
                                    <input type="text" name="answers[{{ $question->id }}]"
                                        value="{{ old('answers.' . $question->id, $question->answer) }}"
                                        placeholder="{{ $question->placeholder }}"
                                        @if (!$canEditProject) readonly @endif>
                                @else
                                    <textarea name="answers[{{ $question->id }}]" placeholder="{{ $question->placeholder }}"
                                        @if (!$canEditProject) readonly @endif>{{ old('answers.' . $question->id, $question->answer) }}</textarea>
                                @endif

                                <div class="project-question-item-help">
                                    {{ filled($question->answered_at) ? 'Atualizado em ' . $question->answered_at->format('d/m/Y H:i') : 'Resposta ainda não registrada.' }}
                                </div>
                            </article>
                        @endforeach

                        <div class="project-question-form-actions">
                            <div class="project-question-note">
                                @if ($canEditProject)
                                    As respostas salvas aqui alimentam a geração do documento do projeto. Quanto melhor o
                                    questionario estiver preenchido, mais consistente fica o texto das 15 seções
                                    obrigatórias.
                                @else
                                    As respostas ficam visiveis para consulta, mas este perfil não pode alterar o
                                    questionario.
                                @endif
                            </div>
                            @if ($canEditProject)
                                <button type="submit" class="btn btn-dark">Salvar respostas</button>
                            @endif
                        </div>
                    </form>
                </section>

                <section class="project-overlap-card" id="project-overlap" data-return-section>
                    <div class="project-overlap-head">
                        <div>
                            <h2>Verificação de sobreposição</h2>
                            <p>
                                Antes da geração final, o sistema compara este projeto com outros projetos da mesma
                                prefeitura para identificar risco de duplicidade, concorrencia tematica ou reaproveitamento
                                de escopo.
                            </p>
                        </div>
                        @if ($canEditProject)
                            <form method="POST" action="{{ route('mayor.projects.overlap.analyze', $project) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary">Verificar sobreposição</button>
                            </form>
                        @endif
                    </div>

                    <div class="project-overlap-summary">
                        <article class="project-overlap-summary-item">
                            <strong>Status</strong>
                            @php
                                $overlapStatus = data_get($overlapAnalysis, 'status', 'pending');
                                $overlapStatusLabel = match ($overlapStatus) {
                                    'clear' => 'Sem conflito relevante',
                                    'attention' => 'Revisão recomendada',
                                    'review_required' => 'Revisão necessária',
                                    default => 'Analise pendente',
                                };
                            @endphp
                            <span
                                class="project-overlap-status status-{{ $overlapStatus }}">{{ $overlapStatusLabel }}</span>
                        </article>
                        <article class="project-overlap-summary-item">
                            <strong>Maior score</strong>
                            <span>{{ data_get($overlapAnalysis, 'highest_score', 0) }}/100</span>
                        </article>
                        <article class="project-overlap-summary-item">
                            <strong>Projetos similares</strong>
                            <span>{{ data_get($overlapAnalysis, 'match_count', 0) }}</span>
                        </article>
                    </div>

                    @if (!empty(data_get($overlapAnalysis, 'matches', [])))
                        <div class="project-overlap-list">
                            @foreach (data_get($overlapAnalysis, 'matches', []) as $match)
                                <article class="project-overlap-item">
                                    <div class="project-overlap-item-top">
                                        <div>
                                            <div class="project-overlap-item-title">
                                                {{ $match['title'] ?? 'Projeto relacionado' }}</div>
                                            <div class="project-overlap-item-meta">
                                                {{ $match['project_type'] ?? 'Tipo não informado' }} ·
                                                {{ $match['status'] ?? 'Status não informado' }}
                                            </div>
                                        </div>
                                        <span
                                            class="project-overlap-status status-{{ ($match['score'] ?? 0) >= 75 ? 'review_required' : (($match['score'] ?? 0) >= 55 ? 'attention' : 'clear') }}">
                                            {{ $match['score'] ?? 0 }}/100
                                        </span>
                                    </div>
                                    <div class="project-overlap-reasons">
                                        @foreach ($match['reasons'] ?? [] as $reason)
                                            <span class="project-overlap-reason">{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="project-section-empty">
                            Nenhum conflito relevante registrado ate agora. Se ainda não houve analise, use o botao acima.
                        </div>
                    @endif
                </section>

                <section class="project-funding-card" id="project-funding" data-return-section>
                    <div class="project-funding-head">
                        <div>
                            <h2>Programas compatíveis e financiamento</h2>
                            <p>
                                O sistema cruza o projeto com programas federais da base municipal e com referencias
                                estaduais aderentes ao tipo e objetivo da proposta.
                            </p>
                        </div>
                        @if ($canEditProject)
                            <form method="POST" action="{{ route('mayor.projects.funding.analyze', $project) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary">Verificar programas compatíveis</button>
                            </form>
                        @endif
                    </div>

                    <div class="project-funding-summary">
                        <article class="project-funding-summary-item">
                            <strong>Status</strong>
                            @php
                                $fundingStatus = data_get($fundingAnalysis, 'status', 'none');
                                $fundingStatusLabel = match ($fundingStatus) {
                                    'strong' => 'Boa aderência',
                                    'moderate' => 'Compatibilidade moderada',
                                    'initial' => 'Sugestoes iniciais',
                                    default => 'Sem match relevante',
                                };
                            @endphp
                            <span
                                class="project-funding-status status-{{ $fundingStatus }}">{{ $fundingStatusLabel }}</span>
                        </article>
                        <article class="project-funding-summary-item">
                            <strong>Maior score</strong>
                            <span>{{ data_get($fundingAnalysis, 'highest_score', 0) }}/100</span>
                        </article>
                        <article class="project-funding-summary-item">
                            <strong>Oportunidades</strong>
                            <span>{{ data_get($fundingAnalysis, 'match_count', 0) }}</span>
                        </article>
                    </div>

                    @if (!empty(data_get($fundingAnalysis, 'matches', [])))
                        <div class="project-funding-list">
                            @foreach (data_get($fundingAnalysis, 'matches', []) as $match)
                                <article class="project-funding-item">
                                    <div class="project-funding-item-top">
                                        <div>
                                            <div class="project-funding-item-title">
                                                {{ $match['title'] ?? 'Programa compativel' }}</div>
                                            <div class="project-funding-item-meta">
                                                {{ $match['subtitle'] ?? 'Origem não informada' }} ·
                                                {{ $match['area'] ?? 'Area não informada' }}
                                            </div>
                                        </div>
                                        <span
                                            class="project-funding-status status-{{ ($match['score'] ?? 0) >= 75 ? 'strong' : (($match['score'] ?? 0) >= 55 ? 'moderate' : 'initial') }}">
                                            {{ $match['score'] ?? 0 }}/100
                                        </span>
                                    </div>
                                    <div class="project-funding-tags">
                                        <span class="project-funding-tag">
                                            {{ ($match['source_type'] ?? 'federal') === 'state' ? 'Estadual' : 'Federal' }}
                                        </span>
                                        <span
                                            class="project-funding-tag">{{ $match['funding_type'] ?? 'modalidade não informada' }}</span>
                                        @if (!empty($match['status']))
                                            <span class="project-funding-tag">{{ $match['status'] }}</span>
                                        @endif
                                    </div>
                                    <div class="project-funding-reasons">
                                        @foreach ($match['reasons'] ?? [] as $reason)
                                            <span class="project-overlap-reason">{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="project-section-empty">
                            Nenhum programa compativel relevante foi registrado ainda. Use o botao acima para rodar a
                            verificação.
                        </div>
                    @endif
                </section>

                <section class="project-sections-card" id="project-document" data-return-section>
                    <div class="project-sections-head">
                        <div>
                            <h2>Documento do projeto</h2>
                            <p>
                                Gere ou regenere o documento consolidado com as 15 seções obrigatórias usando a idéia
                                inicial e as respostas do questionario guiado.
                            </p>
                        </div>
                        <div class="project-sections-actions">
                            <span class="project-chip">
                                {{ data_get($project->metadata, 'generation_status', 'pending') === 'completed' ? 'Documento gerado' : 'Documento pendente' }}
                            </span>
                            <span class="project-chip">Revisão {{ $latestRevision?->revision_number ?? 0 }}</span>
                            @if ($currentDraftRevision)
                                <span class="project-chip">Rascunho {{ $currentDraftRevision->revision_number }}</span>
                            @endif
                            @if ($publishedRevision)
                                <span class="project-chip">Versão final publicada</span>
                            @endif
                            @if (!$publishedRevision)
                                <a href="{{ route('mayor.projects.export.word', $project) }}"
                                    class="btn btn-secondary">Exportar DOCX</a>
                                <a href="{{ route('mayor.projects.export.pdf', $project) }}"
                                    class="btn btn-secondary">Exportar PDF</a>
                            @else
                                <a href="{{ route('mayor.projects.export.word.published', $project) }}"
                                    class="btn btn-secondary">Exportar DOCX final</a>
                                <a href="{{ route('mayor.projects.export.pdf.published', $project) }}"
                                    class="btn btn-secondary">Exportar PDF final</a>
                            @endif
                            @if ($canEditProject)
                                <form method="POST" action="{{ route('mayor.projects.document.generate', $project) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-dark">
                                        {{ data_get($project->metadata, 'generation_status', 'pending') === 'completed' ? 'Regenerar documento' : 'Gerar documento' }}
                                    </button>
                                </form>
                            @elseif($isEditingLocked && $canManageRevisions && $publishedRevision)
                                <form method="POST"
                                    action="{{ route('mayor.projects.revisions.open-draft', $project) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-dark">Abrir novo rascunho</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if ($isEditingLocked && $publishedRevision)
                        <div class="project-generation-notice">
                            <strong>Edição bloqueada pela versão final publicada</strong>
                            <span>
                                Existe uma revisão publicada como documento oficial. Para voltar a editar seções,
                                metadados e geração do projeto, abra um novo rascunho a partir da revisão final
                                publicada.
                            </span>
                        </div>
                    @elseif($publishedRevision)
                        <div class="project-generation-notice">
                            <strong>Exportacao do rascunho bloqueada</strong>
                            <span>
                                Como ja existe uma versão final publicada, o download do documento em trabalho fica
                                bloqueado. Use os botoes de exportacao final para o documento oficial e mantenha o
                                rascunho apenas para revisão interna.
                            </span>
                        </div>
                    @endif

                    @if (data_get($project->metadata, 'generation_status') === 'completed' &&
                            data_get($project->metadata, 'generated_source') === 'fallback')
                        <div class="project-generation-notice">
                            <strong>Documento gerado por fallback</strong>
                            <span>
                                A plataforma não recebeu uma resposta valida da IA dentro do fluxo esperado e preencheu as
                                15 seções com a estrutura de seguranca do sistema. O projeto continua utilizavel, mas vale
                                revisar o texto e, se desejar, tentar a regeneracao depois.
                            </span>
                        </div>
                    @endif

                    <div class="project-sections-list">
                        @foreach ($project->sections as $section)
                            <article class="project-section-item" id="project-document-section-{{ $section->id }}"
                                data-return-section>
                                <div class="project-section-item-top">
                                    <strong>{{ $section->section_order }}. {{ $section->title }}</strong>
                                    <span
                                        class="project-chip">{{ $section->needs_review ? 'A revisar' : 'Revisada' }}</span>
                                </div>
                                <p>{{ $section->description }}</p>

                                @if (filled($section->content))
                                    <div class="project-section-content">
                                        {!! nl2br(e(\Illuminate\Support\Str::limit($section->content, 650))) !!}
                                    </div>
                                @else
                                    <div class="project-section-empty">
                                        Conteudo ainda não gerado. Use a acao de gerar documento para preencher esta secao.
                                    </div>
                                @endif

                                @if ($canEditProject)
                                    <form method="POST"
                                        action="{{ route('mayor.projects.sections.update', [$project, $section]) }}"
                                        class="project-section-edit-form">
                                        @csrf
                                        @method('PUT')
                                        <textarea name="content" placeholder="Atualize o texto consolidado desta secao...">{{ $section->content }}</textarea>
                                        <div class="project-section-edit-row">
                                            <label class="project-section-edit-check">
                                                <input type="checkbox" name="needs_review" value="1"
                                                    @checked($section->needs_review)>
                                                Manter secao marcada para revisão posterior
                                            </label>
                                            <button type="submit" class="btn btn-secondary">Salvar secao</button>
                                        </div>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="project-revisions-card" id="project-revisions" data-return-section>
                    <div class="project-revisions-head">
                        <div>
                            <h2>Revisoes e comparacao de versoes</h2>
                            <p>
                                Cada revisão registra um snapshot do documento e dos metadados estruturados, permitindo
                                comparar o que mudou entre uma versão e a anterior.
                            </p>
                        </div>
                        <span class="project-chip">{{ $documentRevisions->count() }} revisoes recentes</span>
                    </div>

                    @if ($publishedRevision && !$currentDraftRevision)
                        <div class="project-generation-notice project-revision-state-note">
                            <strong>Versão final vigente sem rascunho ativo</strong>
                            <span>
                                A revisão {{ $publishedRevision->revision_number }} e o documento oficial atual. Para
                                voltar a editar o projeto, abra um novo rascunho a partir desta versão final.
                            </span>
                        </div>
                    @elseif ($publishedRevision && $currentDraftRevision)
                        <div class="project-generation-notice project-revision-state-note">
                            <strong>Documento oficial publicado com rascunho em andamento</strong>
                            <span>
                                A revisão {{ $publishedRevision->revision_number }} segue como versão final vigente,
                                enquanto a revisão {{ $currentDraftRevision->revision_number }} concentra as alteracoes
                                em trabalho.
                            </span>
                        </div>
                    @elseif ($currentDraftRevision)
                        <div class="project-generation-notice project-revision-state-note">
                            <strong>Projeto em rascunho ativo</strong>
                            <span>
                                A revisão {{ $currentDraftRevision->revision_number }} e a base atual de trabalho e ainda
                                não foi publicada como versão final.
                            </span>
                        </div>
                    @endif

                    @if ($selectedRevision)
                        @php
                            $selectedCounts = data_get($selectedRevision->comparison_summary, 'counts', [
                                'core' => 0,
                                'structured_metadata' => 0,
                                'sections' => 0,
                            ]);
                            $selectedRevisionStatusLabel = match ($selectedRevision->status) {
                                'approved' => 'Aprovada',
                                'published' => 'Publicada',
                                default => 'Rascunho',
                            };
                            $selectedApprovalSteps = $selectedRevision->approval_steps ?? [];
                            $selectedApprovalCompleted = $selectedRevision->approval_steps_completed_count ?? 0;
                            $selectedApprovalReason = trim((string) ($selectedRevision->approval_reason ?? ''));
                            $selectedPublicationReason = trim((string) ($selectedRevision->publication_reason ?? ''));
                            $selectedApprovalTotal =
                                $selectedRevision->approval_steps_total_count ?? count($selectedApprovalSteps);
                        @endphp

                        @if ($publishedRevision && $currentDraftRevision && $draftPublishedComparison)
                            @php
                                $draftVsPublishedCounts = data_get($draftPublishedComparison, 'counts', [
                                    'core' => 0,
                                    'structured_metadata' => 0,
                                    'sections' => 0,
                                ]);
                            @endphp
                            <div class="project-revision-compare">
                                <div class="project-revision-compare-title">
                                    Rascunho atual {{ $currentDraftRevision->revision_number }} comparado com a versão
                                    final publicada {{ $publishedRevision->revision_number }}
                                </div>
                                <div class="project-revision-compare-note">
                                    Este quadro mostra o que esta em trabalho hoje e ainda não faz parte do documento
                                    oficial publicado.
                                </div>
                                <div class="project-revision-summary">
                                    <article class="project-revision-summary-item">
                                        <strong>Dados centrais</strong>
                                        <span>{{ $draftVsPublishedCounts['core'] ?? 0 }}</span>
                                    </article>
                                    <article class="project-revision-summary-item">
                                        <strong>Metadados</strong>
                                        <span>{{ $draftVsPublishedCounts['structured_metadata'] ?? 0 }}</span>
                                    </article>
                                    <article class="project-revision-summary-item">
                                        <strong>Seções alteradas</strong>
                                        <span>{{ $draftVsPublishedCounts['sections'] ?? 0 }}</span>
                                    </article>
                                </div>

                                @if (!empty(data_get($draftPublishedComparison, 'core_fields', [])))
                                    <div class="project-revision-diff-group">
                                        <h3>Dados centrais em trabalho</h3>
                                        @foreach (data_get($draftPublishedComparison, 'core_fields', []) as $diff)
                                            <div
                                                class="project-revision-diff-item type-{{ $diff['change_type'] ?? 'updated' }}">
                                                <strong>{{ $diff['label'] ?? 'Campo' }}</strong>
                                                <div class="project-revision-diff-panels">
                                                    <div class="project-revision-diff-panel before">
                                                        <strong>Versão final</strong>
                                                        <span>{{ $diff['old'] !== '' ? $diff['old'] : 'Nao informado' }}</span>
                                                    </div>
                                                    <div class="project-revision-diff-panel after">
                                                        <strong>Rascunho atual</strong>
                                                        <span>{{ $diff['new'] !== '' ? $diff['new'] : 'Nao informado' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty(data_get($draftPublishedComparison, 'structured_metadata_fields', [])))
                                    <div class="project-revision-diff-group">
                                        <h3>Metadados em trabalho</h3>
                                        @foreach (data_get($draftPublishedComparison, 'structured_metadata_fields', []) as $diff)
                                            <div
                                                class="project-revision-diff-item type-{{ $diff['change_type'] ?? 'updated' }}">
                                                <strong>{{ $diff['label'] ?? 'Metadado' }}</strong>
                                                <div class="project-revision-diff-panels">
                                                    <div class="project-revision-diff-panel before">
                                                        <strong>Versão final</strong>
                                                        <span>{{ $diff['old'] !== '' ? $diff['old'] : 'Nao informado' }}</span>
                                                    </div>
                                                    <div class="project-revision-diff-panel after">
                                                        <strong>Rascunho atual</strong>
                                                        <span>{{ $diff['new'] !== '' ? $diff['new'] : 'Nao informado' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty(data_get($draftPublishedComparison, 'sections', [])))
                                    <div class="project-revision-diff-group">
                                        <h3>Seções que mudaram no rascunho</h3>
                                        @foreach (data_get($draftPublishedComparison, 'sections', []) as $diff)
                                            <div
                                                class="project-revision-diff-item type-{{ $diff['change_type'] ?? 'updated' }}">
                                                <strong>{{ $diff['section_title'] ?? 'Secao' }}</strong>
                                                <span>
                                                    {{ match ($diff['change_type'] ?? 'updated') {
                                                        'added' => 'preenchida apenas no rascunho atual',
                                                        'removed' => 'conteudo removido no rascunho atual',
                                                        'review_state' => 'estado de revisão alterado no rascunho atual',
                                                        default => 'conteudo alterado no rascunho atual',
                                                    } }}
                                                </span>
                                                <div class="project-revision-diff-panels">
                                                    <div class="project-revision-diff-panel before">
                                                        <strong>Versão final</strong>
                                                        <span>{{ $diff['old_excerpt'] ?? 'Sem conteudo' }}</span>
                                                    </div>
                                                    <div class="project-revision-diff-panel after">
                                                        <strong>Rascunho atual</strong>
                                                        <span>{{ $diff['new_excerpt'] ?? 'Sem conteudo' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if (
                                    ($draftVsPublishedCounts['core'] ?? 0) === 0 &&
                                        ($draftVsPublishedCounts['structured_metadata'] ?? 0) === 0 &&
                                        ($draftVsPublishedCounts['sections'] ?? 0) === 0)
                                    <div class="project-section-empty">
                                        O rascunho atual ainda não difere da versão final publicada.
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="project-revision-summary">
                            <article class="project-revision-summary-item">
                                <strong>Dados centrais</strong>
                                <span>{{ $selectedCounts['core'] ?? 0 }}</span>
                            </article>
                            <article class="project-revision-summary-item">
                                <strong>Metadados</strong>
                                <span>{{ $selectedCounts['structured_metadata'] ?? 0 }}</span>
                            </article>
                            <article class="project-revision-summary-item">
                                <strong>Seções alteradas</strong>
                                <span>{{ $selectedCounts['sections'] ?? 0 }}</span>
                            </article>
                        </div>

                        <div class="project-revision-compare">
                            <div class="project-revision-compare-title">
                                Revisão {{ $selectedRevision->revision_number }}
                                @if ($selectedRevision->previousRevision)
                                    comparada com a revisão {{ $selectedRevision->previousRevision->revision_number }}
                                @else
                                    registrada como base inicial de comparacao
                                @endif
                            </div>
                            <div class="project-revision-compare-note">
                                {{ $selectedRevision->summary ?: 'Revisão criada a partir de alteracoes no projeto.' }}
                            </div>

                            <div class="project-revision-status-row">
                                <span class="project-revision-status status-{{ $selectedRevision->status }}">
                                    {{ $selectedRevisionStatusLabel }}
                                </span>
                                @if ($publishedRevision && $selectedRevision->id === $publishedRevision->id)
                                    <span class="project-chip">Versão final vigente</span>
                                @elseif ($selectedRevision->status === 'published')
                                    <span class="project-chip">Publicacao historica</span>
                                @endif
                                @if ($currentDraftRevision && $selectedRevision->id === $currentDraftRevision->id)
                                    <span class="project-chip">Rascunho ativo</span>
                                @elseif ($selectedRevision->status === 'draft')
                                    <span class="project-chip">Rascunho histórico</span>
                                @endif
                                @if ($selectedRevision->approved_at)
                                    <span class="project-chip">
                                        Aprovada em {{ $selectedRevision->approved_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                                @if ($selectedRevision->published_at)
                                    <span class="project-chip">
                                        Publicada em {{ $selectedRevision->published_at->format('d/m/Y H:i') }}
                                    </span>
                                @endif
                                @if ($selectedRevision->publication_signature_name)
                                    <span class="project-chip">
                                        Assinatura final: {{ $selectedRevision->publication_signature_name }}
                                        @if ($selectedRevision->publication_signature_role)
                                            · {{ $selectedRevision->publication_signature_role }}
                                        @endif
                                    </span>
                                @endif
                                @if ($selectedRevision->restoredFromRevision)
                                    <span class="project-chip">
                                        Restaurada da revisão
                                        {{ $selectedRevision->restoredFromRevision->revision_number }}
                                    </span>
                                @endif
                            </div>

                            @if ($selectedApprovalReason !== '')
                                <div class="project-revision-audit-note">
                                    <strong>Motivo formal da aprovacao</strong>
                                    <span>{{ $selectedApprovalReason }}</span>
                                </div>
                            @endif

                            @if ($selectedPublicationReason !== '' || $selectedRevision->publication_signature_name)
                                <div class="project-revision-audit-note">
                                    <strong>Registro formal da publicacao</strong>
                                    @if ($selectedPublicationReason !== '')
                                        <span>{{ $selectedPublicationReason }}</span>
                                    @endif
                                    @if ($selectedRevision->publication_signature_name)
                                        <span>
                                            Assinatura nominal:
                                            {{ $selectedRevision->publication_signature_name }}
                                            @if ($selectedRevision->publication_signature_role)
                                                · {{ $selectedRevision->publication_signature_role }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            @endif

                            <div class="project-revision-approval-card">
                                <h3>Aprovacao formal por etapas</h3>
                                <p>
                                    Conclua {{ $selectedApprovalCompleted }}/{{ $selectedApprovalTotal }} etapas para
                                    liberar a aprovacao editorial da revisão. A publicacao final so fica disponível
                                    depois da aprovacao completa.
                                </p>
                                <div class="project-revision-approval-list">
                                    @foreach ($selectedApprovalSteps as $step)
                                        <div class="project-revision-approval-item">
                                            <div class="project-revision-approval-item-top">
                                                <div>
                                                    <strong>{{ $step['label'] ?? 'Etapa' }}</strong>
                                                    <span>{{ $step['description'] ?? '' }}</span>
                                                    <span>
                                                        Responsável:
                                                        {{ $step['responsible_name'] ?? 'a definir' }}
                                                    </span>
                                                    @if (!empty($step['completed_by_name']))
                                                        <span>
                                                            Concluida por {{ $step['completed_by_name'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <span
                                                    class="project-revision-step-status {{ !empty($step['approved']) ? 'is-approved' : 'is-pending' }}">
                                                    {{ !empty($step['approved']) ? 'Concluida' : 'Pendente' }}
                                                </span>
                                            </div>
                                            @if ($canManageRevisions && $selectedRevision->status !== 'published')
                                                <form method="POST"
                                                    action="{{ route('mayor.projects.revisions.approval-steps.responsible', [$project, $selectedRevision, $step['key']]) }}"
                                                    data-approval-step-form="true"
                                                    data-step-label="{{ $step['label'] ?? 'Etapa' }}">
                                                    @csrf
                                                    <div class="project-form-field">
                                                        <label>Responsável da etapa</label>
                                                        <select name="responsible_user_id" required
                                                            class="project-form-control" data-approval-step-select="true"
                                                            data-step-key="{{ $step['key'] }}"
                                                            data-step-label="{{ $step['label'] ?? 'Etapa' }}">
                                                            <option value="">Selecione o responsável desta etapa
                                                            </option>
                                                            @foreach ($approvalEligibleUsersByStep[$step['key']] ?? collect() as $approvalUser)
                                                                <option value="{{ $approvalUser->id }}"
                                                                    @selected((int) ($step['responsible_user_id'] ?? 0) === $approvalUser->id)>
                                                                    {{ $approvalUser->name }}{{ $approvalUser->email ? ' · ' . $approvalUser->email : '' }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="project-revision-item-actions">
                                                        <button type="submit" class="btn btn-secondary">
                                                            Definir responsável
                                                        </button>
                                                    </div>
                                                </form>
                                            @endif
                                            @if (!empty($step['approved_at']))
                                                <span>
                                                    Concluida em
                                                    {{ \Illuminate\Support\Carbon::parse($step['approved_at'])->format('d/m/Y H:i') }}
                                                </span>
                                            @endif
                                            @if (
                                                ($canApproveSelectedRevisionSteps[$step['key']] ?? false) &&
                                                    empty($step['approved']) &&
                                                    $selectedRevision->status !== 'published')
                                                <form method="POST"
                                                    action="{{ route('mayor.projects.revisions.approval-steps.approve', [$project, $selectedRevision, $step['key']]) }}"
                                                    class="project-revision-item-actions">
                                                    @csrf
                                                    <button type="submit" class="btn btn-secondary">
                                                        Marcar etapa como concluida
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if ($canManageRevisions)
                                <div class="project-revision-item-actions">
                                    @if ($isEditingLocked && $selectedRevision->status === 'published')
                                        <form method="POST"
                                            action="{{ route('mayor.projects.revisions.open-draft', $project) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-dark">Abrir novo rascunho</button>
                                        </form>
                                    @endif
                                    @if ($selectedRevision->status === 'draft')
                                        <form method="POST"
                                            action="{{ route('mayor.projects.revisions.approve', [$project, $selectedRevision]) }}">
                                            @csrf
                                            <div class="project-form-field">
                                                <label for="approval_reason_{{ $selectedRevision->id }}">Motivo formal da
                                                    aprovacao</label>
                                                <textarea id="approval_reason_{{ $selectedRevision->id }}" name="approval_reason" rows="3" required
                                                    minlength="10" class="project-form-textarea"
                                                    placeholder="Registre o motivo formal da aprovacao desta revisão...">{{ old('approval_reason', $selectedRevision->approval_reason) }}</textarea>
                                            </div>
                                            <button type="submit" class="btn btn-secondary"
                                                @disabled($selectedApprovalCompleted < $selectedApprovalTotal)>
                                                Aprovar revisão</button>
                                        </form>
                                    @endif

                                    @if ($selectedRevision->status === 'approved')
                                        <form method="POST"
                                            action="{{ route('mayor.projects.revisions.publish', [$project, $selectedRevision]) }}">
                                            @csrf
                                            <div class="project-form-field">
                                                <label for="publication_reason_{{ $selectedRevision->id }}">Motivo formal
                                                    da publicacao</label>
                                                <textarea id="publication_reason_{{ $selectedRevision->id }}" name="publication_reason" rows="3" required
                                                    minlength="10" class="project-form-textarea"
                                                    placeholder="Registre o motivo formal da publicacao final desta revisão...">{{ old('publication_reason', $selectedRevision->publication_reason) }}</textarea>
                                            </div>
                                            <button type="submit" class="btn btn-dark" @disabled($selectedApprovalCompleted < $selectedApprovalTotal)>
                                                Publicar versão final</button>
                                        </form>
                                    @endif

                                    <form method="POST"
                                        action="{{ route('mayor.projects.revisions.restore', [$project, $selectedRevision]) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary">Restaurar esta revisão</button>
                                    </form>
                                </div>
                            @endif

                            @if (!empty(data_get($selectedRevision->comparison_summary, 'core_fields', [])))
                                <div class="project-revision-diff-group">
                                    <h3>Dados centrais alterados</h3>
                                    @foreach (data_get($selectedRevision->comparison_summary, 'core_fields', []) as $diff)
                                        <div
                                            class="project-revision-diff-item type-{{ $diff['change_type'] ?? 'updated' }}">
                                            <strong>{{ $diff['label'] ?? 'Campo' }}</strong>
                                            <span>
                                                Alteracao:
                                                {{ match ($diff['change_type'] ?? 'updated') {
                                                    'added' => 'campo preenchido',
                                                    'removed' => 'campo esvaziado',
                                                    default => 'conteudo atualizado',
                                                } }}
                                            </span>
                                            <div class="project-revision-diff-panels">
                                                <div class="project-revision-diff-panel before">
                                                    <strong>Antes</strong>
                                                    <span>{{ $diff['old'] !== '' ? $diff['old'] : 'Nao informado' }}</span>
                                                </div>
                                                <div class="project-revision-diff-panel after">
                                                    <strong>Agora</strong>
                                                    <span>{{ $diff['new'] !== '' ? $diff['new'] : 'Nao informado' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty(data_get($selectedRevision->comparison_summary, 'structured_metadata_fields', [])))
                                <div class="project-revision-diff-group">
                                    <h3>Metadados alterados</h3>
                                    @foreach (data_get($selectedRevision->comparison_summary, 'structured_metadata_fields', []) as $diff)
                                        <div
                                            class="project-revision-diff-item type-{{ $diff['change_type'] ?? 'updated' }}">
                                            <strong>{{ $diff['label'] ?? 'Metadado' }}</strong>
                                            <span>
                                                Alteracao:
                                                {{ match ($diff['change_type'] ?? 'updated') {
                                                    'added' => 'metadado preenchido',
                                                    'removed' => 'metadado removido',
                                                    default => 'metadado ajustado',
                                                } }}
                                            </span>
                                            <div class="project-revision-diff-panels">
                                                <div class="project-revision-diff-panel before">
                                                    <strong>Antes</strong>
                                                    <span>{{ $diff['old'] !== '' ? $diff['old'] : 'Nao informado' }}</span>
                                                </div>
                                                <div class="project-revision-diff-panel after">
                                                    <strong>Agora</strong>
                                                    <span>{{ $diff['new'] !== '' ? $diff['new'] : 'Nao informado' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (!empty(data_get($selectedRevision->comparison_summary, 'sections', [])))
                                <div class="project-revision-diff-group">
                                    <h3>Seções alteradas</h3>
                                    @foreach (data_get($selectedRevision->comparison_summary, 'sections', []) as $diff)
                                        <div
                                            class="project-revision-diff-item type-{{ $diff['change_type'] ?? 'updated' }}">
                                            <strong>{{ $diff['section_title'] ?? 'Secao' }}</strong>
                                            <span>
                                                {{ match ($diff['change_type'] ?? 'updated') {
                                                    'added' => 'secao preenchida pela primeira vez',
                                                    'removed' => 'conteudo removido da secao',
                                                    'review_state' => 'somente o estado de revisão mudou',
                                                    default => 'conteudo da secao foi alterado',
                                                } }}
                                            </span>
                                            <span>
                                                Palavras: {{ $diff['old_words'] ?? 0 }} -> {{ $diff['new_words'] ?? 0 }}
                                            </span>
                                            <div class="project-revision-diff-panels">
                                                <div class="project-revision-diff-panel before">
                                                    <strong>Antes</strong>
                                                    <span>{{ $diff['old_excerpt'] ?? 'Sem conteudo' }}</span>
                                                </div>
                                                <div class="project-revision-diff-panel after">
                                                    <strong>Agora</strong>
                                                    <span>{{ $diff['new_excerpt'] ?? 'Sem conteudo' }}</span>
                                                </div>
                                            </div>
                                            <span>
                                                Revisão: {{ !empty($diff['old_review']) ? 'pendente' : 'ok' }}
                                                ->
                                                {{ !empty($diff['new_review']) ? 'pendente' : 'ok' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if (
                                ($selectedCounts['core'] ?? 0) === 0 &&
                                    ($selectedCounts['structured_metadata'] ?? 0) === 0 &&
                                    ($selectedCounts['sections'] ?? 0) === 0)
                                <div class="project-section-empty">
                                    Esta revisão não trouxe diferencas registradas em relacao a versão anterior.
                                </div>
                            @endif
                        </div>

                        <div class="project-revision-list">
                            @foreach ($documentRevisions as $revision)
                                @php
                                    $revisionCounts = data_get($revision->comparison_summary, 'counts', [
                                        'core' => 0,
                                        'structured_metadata' => 0,
                                        'sections' => 0,
                                    ]);
                                    $revisionStatusLabel = match ($revision->status) {
                                        'approved' => 'Aprovada',
                                        'published' => 'Publicada',
                                        default => 'Rascunho',
                                    };
                                    $revisionApprovalCompleted = $revision->approval_steps_completed_count ?? 0;
                                    $revisionApprovalTotal =
                                        $revision->approval_steps_total_count ?? count($revision->approval_steps ?? []);
                                @endphp
                                <article class="project-revision-item">
                                    <div class="project-revision-item-top">
                                        <div>
                                            <div class="project-revision-item-title">Revisão
                                                {{ $revision->revision_number }}</div>
                                            <div class="project-revision-item-meta">
                                                {{ $revision->summary ?: 'Revisão do projeto registrada.' }} ·
                                                {{ $revision->user?->name ?? 'Sistema' }} ·
                                                {{ $revision->created_at?->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                        <span class="project-chip">{{ $revision->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <div class="project-revision-status-row">
                                        <span class="project-revision-status status-{{ $revision->status }}">
                                            {{ $revisionStatusLabel }}
                                        </span>
                                        @if ($revision->published_at)
                                            <span class="project-chip">Versão final publicada</span>
                                        @endif
                                    </div>
                                    <div class="project-revision-item-meta">
                                        Dados centrais: {{ $revisionCounts['core'] ?? 0 }} ·
                                        Metadados: {{ $revisionCounts['structured_metadata'] ?? 0 }} ·
                                        Seções: {{ $revisionCounts['sections'] ?? 0 }} ·
                                        Etapas: {{ $revisionApprovalCompleted }}/{{ $revisionApprovalTotal }}
                                    </div>
                                    <div class="project-revision-item-actions">
                                        <a href="{{ route('mayor.projects.show', ['project' => $project, 'compare_revision' => $revision->id]) }}"
                                            class="btn btn-secondary">Comparar esta revisão</a>
                                        @if ($canManageRevisions)
                                            <form method="POST"
                                                action="{{ route('mayor.projects.revisions.restore', [$project, $revision]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary">Restaurar</button>
                                            </form>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="project-section-empty">
                            Ainda não ha revisoes documentais registradas. Gere o documento ou atualize seções/metadados
                            para iniciar o versionamento.
                        </div>
                    @endif
                </section>

                <section class="project-history-card" id="project-history" data-return-section>
                    <div class="project-history-head">
                        <div>
                            <h2>Histórico de edições</h2>
                            <p>
                                Linha do tempo das principais alteracoes do projeto, incluindo questionario, documento,
                                colaboracao e exportacoes.
                            </p>
                        </div>
                        <span class="project-chip">{{ $editHistory->count() }} registros recentes</span>
                    </div>

                    @if ($editHistory->isNotEmpty())
                        @php
                            $historyPriorityActions = [
                                'project_created',
                                'project_revision_approved',
                                'project_revision_published',
                                'project_revision_superseded',
                                'project_revision_restored',
                                'project_working_draft_opened',
                                'project_revision_step_approved',
                                'project_revision_step_responsible_assigned',
                                'project_collaborator_invited',
                                'project_collaborator_accepted',
                                'project_collaborator_removed',
                                'project_permission_blocked',
                                'project_exported_published_docx',
                                'project_exported_published_pdf',
                            ];

                            $primaryEditHistory = $editHistory
                                ->filter(fn($entry) => in_array($entry->action, $historyPriorityActions, true))
                                ->take(2)
                                ->values();

                            if ($primaryEditHistory->isEmpty()) {
                                $primaryEditHistory = $editHistory->take(6)->values();
                            }

                            $primaryHistoryIds = $primaryEditHistory->pluck('id')->all();
                            $secondaryEditHistory = $editHistory
                                ->reject(fn($entry) => in_array($entry->id, $primaryHistoryIds, true))
                                ->values();

                            $historyPresenter = function ($entry) {
                                $title = match ($entry->action) {
                                    'project_created' => 'Projeto criado',
                                    'project_questions_generated' => 'Perguntas dinâmicas geradas',
                                    'project_questions_regenerated' => 'Perguntas dinâmicas regeneradas',
                                    'project_question_answered' => 'Resposta do questionario atualizada',
                                    'project_metadata_updated' => 'Metadados do projeto atualizados',
                                    'project_revision_created' => 'Revisão documental registrada',
                                    'project_working_draft_opened' => 'Novo rascunho aberto',
                                    'project_revision_step_responsible_assigned' => 'Responsável de etapa definido',
                                    'project_revision_step_approved' => 'Etapa formal aprovada',
                                    'project_revision_approved' => 'Revisão aprovada',
                                    'project_revision_published' => 'Revisão publicada',
                                    'project_revision_superseded' => 'Versão final substituida',
                                    'project_revision_restored' => 'Revisão restaurada',
                                    'project_section_generated' => 'Secao do documento gerada',
                                    'project_section_updated' => 'Secao do documento editada manualmente',
                                    'project_overlap_analyzed' => 'Analise de sobreposição executada',
                                    'project_funding_analyzed' => 'Analise de financiamento executada',
                                    'project_collaborator_invited' => 'Convite de colaboracao enviado',
                                    'project_collaborator_accepted' => 'Convite de colaboracao aceito',
                                    'project_collaborator_removed' => 'Colaborador removido',
                                    'project_permission_blocked' => 'Acao bloqueada por permissao',
                                    'project_exported_docx' => 'Exportacao em DOCX preparada',
                                    'project_exported_published_docx' => 'Exportacao da versão final em DOCX preparada',
                                    'project_exported_word' => 'Exportacao em Word preparada',
                                    'project_exported_pdf' => 'Exportacao em PDF preparada',
                                    'project_exported_published_pdf' => 'Exportacao da versão final em PDF preparada',
                                    default => str_replace('_', ' ', $entry->action),
                                };

                                $description = match ($entry->action) {
                                    'project_created' => 'Estrutura inicial criada a partir da idéia base do projeto.',
                                    'project_questions_generated',
                                    'project_questions_regenerated'
                                        => 'Fluxo dinamico ajustado conforme o tipo do projeto e o contexto registrado.',
                                    'project_question_answered' => data_get(
                                        $entry->metadata,
                                        'question_text',
                                        'Uma resposta do questionario foi atualizada.',
                                    ),
                                    'project_metadata_updated' => (data_get($entry->metadata, 'label') ?: 'Metadado') .
                                        ' · Escopo: ' .
                                        (data_get($entry->metadata, 'scope') === 'core'
                                            ? 'dados centrais'
                                            : 'dados operacionais'),
                                    'project_revision_created' => 'Revisão ' .
                                        data_get($entry->metadata, 'revision_number', '-') .
                                        ' criada por ' .
                                        str_replace(
                                            '_',
                                            ' ',
                                            data_get($entry->metadata, 'trigger_action', 'alteracoes'),
                                        ) .
                                        ' · seções: ' .
                                        data_get($entry->metadata, 'changed_sections', 0) .
                                        ', dados centrais: ' .
                                        data_get($entry->metadata, 'changed_core_fields', 0) .
                                        ', metadados: ' .
                                        data_get($entry->metadata, 'changed_structured_fields', 0) .
                                        '.',
                                    'project_working_draft_opened' => 'Rascunho aberto a partir da revisão final ' .
                                        data_get($entry->metadata, 'source_revision_number', '-') .
                                        '.',
                                    'project_revision_step_responsible_assigned' => (data_get(
                                        $entry->metadata,
                                        'step_label',
                                    ) ?:
                                        'Etapa') .
                                        ' agora esta sob responsabilidade de ' .
                                        (data_get($entry->metadata, 'responsible_name') ?: 'usuario designado') .
                                        '.',
                                    'project_revision_step_approved' => (data_get($entry->metadata, 'step_label') ?:
                                        'Etapa') .
                                        ' concluida na revisão ' .
                                        data_get($entry->metadata, 'revision_number', '-') .
                                        '.',
                                    'project_revision_approved'
                                        => 'Revisão aprovada para seguir no fluxo editorial. Motivo: ' .
                                        (data_get($entry->metadata, 'reason') ?: 'não informado') .
                                        '.',
                                    'project_revision_published'
                                        => 'Revisão publicada como versão final do documento. Motivo: ' .
                                        (data_get($entry->metadata, 'reason') ?: 'não informado') .
                                        '. Assinatura: ' .
                                        (data_get($entry->metadata, 'signature_name') ?: 'não registrada') .
                                        (data_get($entry->metadata, 'signature_role')
                                            ? ' · ' . data_get($entry->metadata, 'signature_role')
                                            : ''),
                                    'project_revision_superseded' => 'A revisão ' .
                                        data_get($entry->metadata, 'revision_number', '-') .
                                        ' deixou de ser a versão final vigente apos a publicacao da revisão ' .
                                        data_get($entry->metadata, 'superseded_by_revision_number', '-') .
                                        '.',
                                    'project_revision_restored' => 'O projeto foi restaurado a partir da revisão ' .
                                        data_get($entry->metadata, 'revision_number', '-') .
                                        '.',
                                    'project_section_generated' => data_get(
                                        $entry->metadata,
                                        'section_title',
                                        'Uma secao do documento foi atualizada.',
                                    ) .
                                        ' · Fonte: ' .
                                        (data_get($entry->metadata, 'source', 'fallback') === 'ai' ? 'IA' : 'fallback'),
                                    'project_section_updated' => data_get(
                                        $entry->metadata,
                                        'section_title',
                                        'Uma secao do documento foi editada.',
                                    ) .
                                        ' · Revisão pendente: ' .
                                        (data_get($entry->metadata, 'needs_review') ? 'sim' : 'nao'),
                                    'project_overlap_analyzed'
                                        => 'Verificação de sobreposição rodada para reduzir conflito com outros projetos.',
                                    'project_funding_analyzed'
                                        => 'Busca por programas compatíveis e oportunidades de financiamento concluida.',
                                    'project_collaborator_invited' => 'Convite enviado para ' .
                                        (data_get($entry->metadata, 'invitee_name') ?: 'usuario') .
                                        ' com permissao de ' .
                                        (data_get($entry->metadata, 'permission') === 'viewer'
                                            ? 'visualizacao'
                                            : 'edição') .
                                        '.',
                                    'project_collaborator_accepted'
                                        => 'Colaborador confirmou a participacao no projeto.',
                                    'project_collaborator_removed'
                                        => 'A colaboracao ou o convite pendente foi encerrado neste projeto.',
                                    'project_permission_blocked' => 'Tentativa bloqueada ao executar "' .
                                        str_replace('_', ' ', data_get($entry->metadata, 'attempted_action', 'acao')) .
                                        '" com perfil ' .
                                        data_get($entry->metadata, 'actual_role', 'sem permissao') .
                                        '.',
                                    'project_exported_word'
                                        => 'Documento preparado para download em formato compativel com Word.',
                                    'project_exported_docx'
                                        => 'Documento gerado em formato .docx por biblioteca dedicada no servidor.',
                                    'project_exported_published_docx'
                                        => 'Versão final publicada exportada em formato .docx.',
                                    'project_exported_pdf'
                                        => 'Documento gerado em PDF por biblioteca dedicada no servidor.',
                                    'project_exported_published_pdf' => 'Versão final publicada exportada em PDF.',
                                    default => 'Atualizacao registrada no histórico do projeto.',
                                };

                                return [
                                    'title' => ucfirst($title),
                                    'description' => $description,
                                ];
                            };
                        @endphp

                        <div class="project-history-note">
                            Exibindo apenas os eventos principais da linha do tempo. Os demais registros ficam
                            disponiveis em um bloco expansivel logo abaixo.
                        </div>

                        <div class="project-history-list">
                            @foreach ($primaryEditHistory as $entry)
                                @php
                                    $historyView = $historyPresenter($entry);
                                @endphp
                                <article class="project-history-item">
                                    <div class="project-history-item-top">
                                        <div>
                                            <div class="project-history-item-title">{{ $historyView['title'] }}</div>
                                            <div class="project-history-item-meta">
                                                {{ $entry->user?->name ?? 'Sistema' }}
                                                @if ($entry->section?->title)
                                                    · {{ $entry->section->title }}
                                                @endif
                                                · {{ $entry->created_at?->format('d/m/Y H:i') }}
                                            </div>
                                        </div>
                                        <span class="project-chip">{{ $entry->created_at?->diffForHumans() }}</span>
                                    </div>
                                    <div class="project-history-item-body">
                                        {{ $historyView['description'] }}
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        @if ($secondaryEditHistory->isNotEmpty())
                            <details class="project-history-more">
                                <summary>
                                    <span>Ver mais registros do histórico</span>
                                    <span class="project-chip">{{ $secondaryEditHistory->count() }} itens</span>
                                </summary>
                                <div class="project-history-more-body">
                                    <div class="project-history-list">
                                        @foreach ($secondaryEditHistory as $entry)
                                            @php
                                                $historyView = $historyPresenter($entry);
                                            @endphp
                                            <article class="project-history-item">
                                                <div class="project-history-item-top">
                                                    <div>
                                                        <div class="project-history-item-title">
                                                            {{ $historyView['title'] }}</div>
                                                        <div class="project-history-item-meta">
                                                            {{ $entry->user?->name ?? 'Sistema' }}
                                                            @if ($entry->section?->title)
                                                                · {{ $entry->section->title }}
                                                            @endif
                                                            · {{ $entry->created_at?->format('d/m/Y H:i') }}
                                                        </div>
                                                    </div>
                                                    <span
                                                        class="project-chip">{{ $entry->created_at?->diffForHumans() }}</span>
                                                </div>
                                                <div class="project-history-item-body">
                                                    {{ $historyView['description'] }}
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endif
                    @else
                        <div class="project-section-empty">
                            Ainda não ha registros suficientes para compor uma linha do tempo visivel deste projeto.
                        </div>
                    @endif
                </section>
            </div>

            <aside class="project-side">
                <section class="project-side-card">
                    <h3>Governanca inicial</h3>
                    <p>Informacoes principais salvas na criacao da estrutura do projeto.</p>
                    <div class="project-side-list">
                        <div class="project-side-item">
                            <strong>Criador</strong>
                            <span>{{ $project->owner?->name ?? 'Usuario' }}</span>
                            <small>{{ $project->owner?->email }}</small>
                        </div>
                        <div class="project-side-item">
                            <strong>Secretaria responsável</strong>
                            <span>{{ $project->responsible_secretariat ?: 'A definir' }}</span>
                        </div>
                        <div class="project-side-item">
                            <strong>Fase atual</strong>
                            <span>{{ ucfirst(str_replace('_', ' ', $project->current_phase)) }}</span>
                        </div>
                    </div>
                </section>

                <section class="project-side-card" id="project-collaborators" data-return-section>
                    <h3>Colaboradores</h3>
                    <p>Convide usuarios do mesmo município para acompanhar ou editar o projeto.</p>
                    <div class="project-side-list">
                        @forelse ($activeCollaborators as $collaborator)
                            <div class="project-side-item">
                                <strong>{{ $collaborator->user?->name ?? 'Usuario' }}</strong>
                                <span>Permissao:
                                    {{ $collaborator->permission === 'viewer' ? 'Visualizacao' : 'Edição' }}</span>
                                <small>
                                    Convidado por {{ $collaborator->invitedBy?->name ?? 'Sistema' }} · ativo desde
                                    {{ $collaborator->accepted_at?->format('d/m/Y H:i') }}
                                </small>
                                @if ($canManageCollaborators)
                                    <div class="project-side-item-actions">
                                        <form method="POST"
                                            action="{{ route('mayor.projects.collaborators.remove', [$project, $collaborator]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary">Remover</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="project-side-item">
                                <strong>Nenhum colaborador ativo ainda</strong>
                                <span>Usuarios elegiveis na prefeitura: {{ $eligibleUsers->count() }}</span>
                            </div>
                        @endforelse
                    </div>

                    @if ($pendingCollaborators->isNotEmpty())
                        <div class="project-placeholder-note">
                            Convites pendentes: {{ $pendingCollaborators->count() }}
                        </div>

                        <div class="project-side-list">
                            @foreach ($pendingCollaborators as $collaborator)
                                <div class="project-side-item">
                                    <strong>{{ $collaborator->user?->name ?? 'Usuario' }}</strong>
                                    <span>Convite pendente ·
                                        {{ $collaborator->permission === 'viewer' ? 'Visualizacao' : 'Edição' }}</span>
                                    <small>
                                        Enviado em {{ $collaborator->invited_at?->format('d/m/Y H:i') }}
                                    </small>
                                    @if ($canManageCollaborators)
                                        <div class="project-side-item-actions">
                                            <form method="POST"
                                                action="{{ route('mayor.projects.collaborators.remove', [$project, $collaborator]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-secondary">Cancelar convite</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($canManageCollaborators)
                        <form method="POST" action="{{ route('mayor.projects.collaborators.invite', $project) }}"
                            class="project-collab-form">
                            @csrf
                            <div class="project-collab-grid">
                                <select name="user_id" required>
                                    <option value="">Selecionar usuario</option>
                                    @foreach ($eligibleUsers as $eligibleUser)
                                        <option value="{{ $eligibleUser->id }}">
                                            {{ $eligibleUser->name }} · {{ $eligibleUser->email }}
                                        </option>
                                    @endforeach
                                </select>

                                <select name="permission" required>
                                    <option value="editor">Editor</option>
                                    <option value="viewer">Visualizador</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-dark" @disabled($eligibleUsers->isEmpty())>
                                {{ $eligibleUsers->isEmpty() ? 'Nenhum usuario elegivel' : 'Convidar colaborador' }}
                            </button>
                        </form>
                    @endif
                </section>
            </aside>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const projectForms = Array.from(document.querySelectorAll('.project-show-page form'));
            const selects = Array.from(document.querySelectorAll('[data-approval-step-select="true"]'));

            const ensureHiddenInput = (form, name) => {
                let input = form.querySelector(`input[name="${name}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    form.appendChild(input);
                }

                return input;
            };

            const resolveReturnFragment = (form, submitter = null) => {
                const section =
                    submitter?.closest('[data-return-section]') ||
                    form.closest('[data-return-section]');

                if (section?.id) {
                    return section.id;
                }

                return window.location.hash ? window.location.hash.replace(/^#/, '') : '';
            };

            const hasDuplicateForSelect = (currentSelect) => {
                const selectedValue = currentSelect.value;
                if (!selectedValue) {
                    return false;
                }

                return selects.some((select) => select !== currentSelect && select.value === selectedValue);
            };

            projectForms.forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const fragment = resolveReturnFragment(form, event.submitter || null);
                    const compareRevision = new URLSearchParams(window.location.search).get(
                        'compare_revision') || '';

                    ensureHiddenInput(form, 'return_fragment').value = fragment;
                    ensureHiddenInput(form, 'return_compare_revision').value = compareRevision;
                });
            });

            selects.forEach((select) => {
                select.dataset.previousValue = select.value;

                select.addEventListener('focus', () => {
                    select.dataset.previousValue = select.value;
                });

                select.addEventListener('change', () => {
                    if (!hasDuplicateForSelect(select)) {
                        select.dataset.previousValue = select.value;
                        return;
                    }

                    const stepLabel = select.dataset.stepLabel || 'esta etapa';
                    const selectedOption = select.options[select.selectedIndex];
                    const responsibleName = selectedOption ? selectedOption.text.replace(/\s+·.*$/,
                        '').trim() : 'este usuario';

                    window.alert(
                        `O usuario ${responsibleName} ja esta designado em outra etapa desta revisão. Escolha outro responsável para ${stepLabel}.`
                    );

                    select.value = select.dataset.previousValue || '';
                });
            });

            document.querySelectorAll('[data-approval-step-form="true"]').forEach((form) => {
                form.addEventListener('submit', (event) => {
                    const select = form.querySelector('[data-approval-step-select="true"]');
                    if (!select || !hasDuplicateForSelect(select)) {
                        return;
                    }

                    const stepLabel = select.dataset.stepLabel || 'esta etapa';
                    const selectedOption = select.options[select.selectedIndex];
                    const responsibleName = selectedOption ? selectedOption.text.replace(/\s+·.*$/,
                        '').trim() : 'este usuario';

                    event.preventDefault();
                    window.alert(
                        `O usuario ${responsibleName} ja esta designado em outra etapa desta revisão. Escolha outro responsável para ${stepLabel}.`
                    );
                    select.focus();
                });
            });
        });
    </script>
@endpush
