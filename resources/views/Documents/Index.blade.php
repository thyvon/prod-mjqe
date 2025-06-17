<!DOCTYPE html>
<html lang="en"> 
<head>
    <title>E-Purchasing Docs</title>
    
    <!-- Meta -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">  
    <meta name="description" content="E-Purchasing Documentation">
    <meta name="keywords" content="E-Purchasing, Documentation, MJQ Education">
    <meta name="author" content="MJQ Education">   
    <link rel="icon" type="image/x-icon" href="https://www.mjqeducation.edu.kh/FrontEnd/Image/logo/mjq-education-single-logo_1.ico">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome JS-->
    <script defer src="{{ asset('documentation/fontawesome/js/all.min.js') }}" defer></script>
    
    <!-- Plugins CSS -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.2/styles/atom-one-dark.min.css">
    <link rel="stylesheet" href="{{ asset('documentation/plugins/simplelightbox/simple-lightbox.min.css') }}" defer>

    <!-- Theme CSS -->  
    <link id="theme-style" rel="stylesheet" href="{{ asset('documentation/css/theme.css') }}" defer>

    <style>
        #docs-sidebar-toggler span {
            display: block;
            width: 25px; /* Adjust width */
            height: 3px; /* Adjust height */
            margin: 5px auto; /* Adjust spacing */
            background-color: white;
        }
        @font-face {
  font-family: 'Tw Cen MT';
  src: url('/Fonts/TwCenMT.woff2') format('woff2');
}

@font-face {
  font-family: 'Khmer OS Content';
  src: url('/Fonts/KhmerOScontent.woff2') format('woff2');
}

@font-face {
  font-family: 'Khmer OS Moul Light';
  src: url('/Fonts/KhmerOSmuollight.woff2') format('woff2');
}
    </style>

</head> 

<body class="docs-page">    
    <header class="header fixed-top bg-dark">	    
        <div class="branding docs-branding">
            <div class="container-fluid position-relative py-2">
                <div class="docs-logo-wrapper">
					<button id="docs-sidebar-toggler" class="docs-sidebar-toggler docs-sidebar-visible me-2 d-xl-none" type="button">
	                    <span></span>
	                    <span></span>
	                    <span></span>
	                </button>
	                <div class="site-logo">
                        <a class="navbar-brand" href="/">
                            <img class="logo-icon me-2" src="{{ asset('documentation/images/coderdocs-logo.svg') }}" alt="logo">
                            <span class="logo-text text-white">E-Purchasing <span class="text-alt">Docs</span></span>
                        </a>
                        <a href="/" class="ms-2 text-white">Go to Demo</a>
                    </div>
                    <!--//site-logo-->    
                </div><!--//docs-logo-wrapper-->
	            <div class="docs-top-utilities d-flex justify-content-end align-items-center">
	                <!-- <div class="top-search-box d-none d-lg-flex">
		                <form class="search-form">
				            <input type="text" placeholder="Search the docs..." name="search" class="form-control search-input">
				            <button type="submit" class="btn search-btn" value="Search"><i class="fas fa-search"></i></button>
				        </form>
	                </div> -->
	
					<ul class="social-list list-inline mx-md-3 mx-lg-5 mb-0 d-none d-lg-flex">
						<li class="list-inline-item"><a href="https://github.com/thyvon/prod-mjqe"><i class="fa-brands fa-github fa-fw"></i></a></li>
			            <!-- <li class="list-inline-item"><a href="#"><i class="fa-brands fa-x-twitter fa-fw"></i></a></li>
		                <li class="list-inline-item"><a href="#"><i class="fa-brands fa-slack fa-fw"></i></a></li>
		                <li class="list-inline-item"><a href="#"><i class="fa-brands fa-product-hunt fa-fw"></i></a></li> -->
		            </ul><!--//social-list-->
		            <a href="https://github.com/thyvon/prod-mjqe/archive/refs/heads/main.zip" class="btn btn-primary d-none d-lg-flex">Download</a>
	            </div><!--//docs-top-utilities-->
            </div><!--//container-->
        </div><!--//branding-->
    </header><!--//header-->
    
    
	<div class="docs-wrapper">
    @include('Documents.navigation') <!-- Sidebar navigation -->
    
    <div class="docs-content">
        <div class="container">
            <!-- Loop through documents and render them in the content area -->
            @php $parentIndex = 1; @endphp
				@foreach($documents as $document)
					<article class="docs-article" id="{{ Str::slug($document->section_name) }}">
						<header class="docs-header">
							<h1 class="docs-heading">
								{{ $parentIndex }}. {{ $document->section_name }} 
								<span class="docs-time">
                                Last updated: {{ $document->updated_at->diffForHumans() }}
                            	</span>
							</h1>
							<section class="docs-intro">
								<p>{!! $document->description !!}</p>
							</section>
						</header>
						<!-- Loop through document items (subsections) under each document -->
						@foreach($document->items as $item)
							<section class="docs-section" id="{{ Str::slug($item->article_name) }}">
								<h2 class="section-heading">
									{{ $parentIndex }}.{{ $loop->iteration }} {{ $item->article_name }}
								</h2>
								<p>{!! $item->description !!}</p>
							</section>
						@endforeach
					</article>
					@php $parentIndex++; @endphp
				@endforeach
			</div>
		</div><!--//docs-content-->
	</div><!--//docs-wrapper-->

   
       
    <!-- Javascript -->          
    <script src="{{ asset('documentation/plugins/popper.min.js') }}"></script>
    <script src="{{ asset('documentation/plugins/bootstrap/js/bootstrap.min.js') }}"></script>  
    
    
    <!-- Page Specific JS -->
    <script src="{{ asset('documentation/plugins/smoothscroll.min.js') }}" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.8/highlight.min.js') }}"></script>
    <script src="{{ asset('documentation/js/highlight-custom.js') }}" defer></script> 
    <script src="{{ asset('documentation/plugins/simplelightbox/simple-lightbox.min.js') }}" defer></script>      
    <script src="{{ asset('documentation/plugins/gumshoe/gumshoe.polyfills.min.js') }}" defer></script> 
    <script src="{{ asset('documentation/js/docs.js') }}" defer></script> 

</body>
</html> 

