<div id="docs-sidebar" class="docs-sidebar">
    <div class="top-search-box d-lg-none p-3">
        <form class="search-form">
            <input type="text" placeholder="Search the docs..." name="search" class="form-control search-input">
            <button type="submit" class="btn search-btn" value="Search"><i class="fas fa-search"></i></button>
        </form>
    </div>

    <nav id="docs-nav" class="docs-nav navbar">
        <ul class="section-items list-unstyled nav flex-column pb-3">
            <!-- Initialize a counter for parent (documents) -->
            @php $parentIndex = 1; @endphp
            
            <!-- Loop through documents -->
            @foreach($documents as $document)
                <!-- Display the parent document -->
                <li class="nav-item section-title">
                    <a class="nav-link scrollto" href="#{{ Str::slug($document->section_name) }}">
                        <span class="theme-icon-holder me-2">
                        <i class="{{ $document->icon_class }}"></i>
                        </span>
                        {{ $parentIndex }}. {{ $document->section_name }}
                    </a>
                </li>

                <!-- Initialize a counter for child items -->
                @php $childIndex = 1; @endphp

                <!-- Loop through document items (child sections) under each document -->
                @foreach($document->items as $item)
                    <li class="nav-item">
                        <a class="nav-link scrollto" href="#{{ Str::slug($item->article_name) }}">
                            {{ $parentIndex }}.{{ $childIndex }} {{ $item->article_name }}
                        </a>
                    </li>
                    @php $childIndex++; @endphp
                @endforeach

                <!-- Increment the parent index for the next document -->
                @php $parentIndex++; @endphp
            @endforeach
        </ul>
    </nav><!--//docs-nav-->
</div><!--//docs-sidebar-->
