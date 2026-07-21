<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-900">{{ __('dashboard.submission_heading') }}</h2>
    </x-slot>

    <form method="POST" action="{{ route('submissions.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center gap-2 text-red-700 font-medium text-sm mb-2">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                    {{ __('dashboard.error_header') }}
                </div>
                <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('article.info_heading') }}</h3>

            <div class="space-y-4">
                <div>
                    <x-input-label for="title" :value="__('article.title_label')" />
                    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                    <x-input-error :messages="$errors->get('title')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="category_id" :value="__('article.section')" />
                    <select id="category_id" name="category_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>
                        <option value="">{{ __('article.select_section') }}</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="abstract_ru" :value="__('article.abstract_label')" />
                    <textarea id="abstract_ru" name="abstract_ru" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" required>{{ old('abstract_ru') }}</textarea>
                    <x-input-error :messages="$errors->get('abstract_ru')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="abstract_en" :value="__('article.abstract_en_label')" />
                    <textarea id="abstract_en" name="abstract_en" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm">{{ old('abstract_en') }}</textarea>
                    <x-input-error :messages="$errors->get('abstract_en')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="keywords" :value="__('article.keywords_label')" />
                    <x-text-input id="keywords" name="keywords" type="text" class="mt-1 block w-full" :value="old('keywords')" placeholder="через запятую: образование, методика, РКИ" />
                    <p class="text-xs text-gray-400 mt-1">Укажите ключевые слова через запятую</p>
                    <x-input-error :messages="$errors->get('keywords')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="references" :value="__('article.references_label')" />
                    <textarea id="references" name="references" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" placeholder="Одна ссылка на строку. DOI будет извлечён автоматически.">{{ old('references') }}</textarea>
                    <p class="text-xs text-gray-400 mt-1">По одной ссылке на строку</p>
                    <x-input-error :messages="$errors->get('references')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="pdf_file" :value="__('article.file_label')" />
                    <input id="pdf_file" name="pdf_file" type="file" accept=".pdf,.doc,.docx" required
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    <p class="text-xs text-gray-400 mt-1">{{ __('article.file_hint') }}</p>
                    <x-input-error :messages="$errors->get('pdf_file')" class="mt-1" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6" x-data="{
            coauthors: @json(old('coauthors', [])),
            addCoauthor() { this.coauthors.push({ full_name: '', degree: '', position: '', organization: '', orcid: '' }); },
            removeCoauthor(i) { this.coauthors.splice(i, 1); }
        }">
            <h3 class="font-semibold text-gray-900 mb-4">Авторы</h3>

            <div class="border border-gray-100 rounded-lg p-4 mb-4">
                <div class="text-xs text-gray-400 uppercase font-medium mb-3">{{ __('article.main_author') }}</div>
                <div class="space-y-3">
                    <div>
                        <x-input-label for="author_name" :value="__('article.full_name')" />
                        <x-text-input id="author_name" name="author_name" type="text" class="mt-1 block w-full" :value="old('author_name', auth()->user()->full_name)" required />
                        <x-input-error :messages="$errors->get('author_name')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="author_degree" :value="__('article.degree')" />
                            <x-text-input id="author_degree" name="author_degree" type="text" class="mt-1 block w-full" :value="old('author_degree')" />
                        </div>
                        <div>
                            <x-input-label for="author_position" :value="__('article.position')" />
                            <x-text-input id="author_position" name="author_position" type="text" class="mt-1 block w-full" :value="old('author_position')" />
                        </div>
                    </div>
                    <div>
                        <x-input-label for="author_organization" :value="__('article.affiliation')" />
                        <x-text-input id="author_organization" name="author_organization" type="text" class="mt-1 block w-full" :value="old('author_organization')" />
                    </div>
                    <div>
                        <x-input-label for="author_orcid" :value="__('article.orcid')" />
                        <x-text-input id="author_orcid" name="author_orcid" type="text" class="mt-1 block w-full" :value="old('author_orcid')" placeholder="0000-0000-0000-0000" />
                    </div>
                </div>
            </div>

            <template x-for="(coauthor, index) in coauthors" :key="index">
                <div class="border border-gray-100 rounded-lg p-4 mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-xs text-gray-400 uppercase font-medium" x-text="'Соавтор ' + (index + 1)"></div>
                        <button type="button" @click="removeCoauthor(index)" class="text-xs text-red-500 hover:text-red-700">{{ __('article.remove_coauthor') }}</button>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <x-input-label :value="__('article.full_name')" />
                            <input type="text" :name="'coauthors[' + index + '][full_name]'" x-model="coauthor.full_name" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" />
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label :value="__('article.degree')" />
                                <input type="text" :name="'coauthors[' + index + '][degree]'" x-model="coauthor.degree" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" />
                            </div>
                            <div>
                                <x-input-label :value="__('article.position')" />
                                <input type="text" :name="'coauthors[' + index + '][position]'" x-model="coauthor.position" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" />
                            </div>
                        </div>
                        <div>
                            <x-input-label :value="__('article.affiliation')" />
                            <input type="text" :name="'coauthors[' + index + '][organization]'" x-model="coauthor.organization" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" />
                        </div>
                        <div>
                            <x-input-label :value="__('article.orcid')" />
                            <input type="text" :name="'coauthors[' + index + '][orcid]'" x-model="coauthor.orcid" placeholder="0000-0000-0000-0000" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary focus:ring-primary text-sm" />
                        </div>
                    </div>
                </div>
            </template>

            @if($errors->has('coauthors.*'))
                <p class="text-sm text-red-600 mb-3">{{ __('article.check_coauthors') }}</p>
            @endif

            <button type="button" @click="addCoauthor()" class="text-sm text-primary hover:underline">{{ __('article.add_coauthor') }}</button>
        </div>

        @if($agreement)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="font-semibold text-gray-900 mb-4">{{ __('article.copyright_agreement_heading') }}</h3>
            <div class="text-sm text-gray-700 leading-relaxed mb-4 whitespace-pre-line">{{ $agreement->short_text }}</div>
            <a href="{{ route('agreements.show', $agreement) }}" target="_blank" class="text-sm text-primary hover:underline">
                {{ __('article.copyright_agreement_full_text') }}
            </a>
            <label class="flex items-start gap-2 mt-4 cursor-pointer">
                <input type="checkbox" name="agreement_accepted" value="1" class="mt-1 rounded border-gray-300 text-primary focus:ring-primary" {{ old('agreement_accepted') ? 'checked' : '' }} />
                <span class="text-sm text-gray-700">{{ __('article.copyright_agreement_accept_label') }}</span>
            </label>
            @error('agreement_accepted')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        @endif

        <div class="flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="text-sm text-gray-400 hover:text-gray-600">{{ __('common.cancel') }}</a>
            <x-primary-button>{{ __('article.submit_manuscript') }}</x-primary-button>
        </div>
    </form>
</x-app-layout>
